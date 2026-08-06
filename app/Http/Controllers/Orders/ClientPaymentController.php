<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientPaymentController extends Controller
{
    public function orders(Client $client): JsonResponse
    {
        $paymentTotals = DB::table('client_payments')
            ->selectRaw('order_id, SUM(amount) as total')
            ->whereNotNull('order_id')
            ->groupBy('order_id');

        $orders = $client->orders()
            ->leftJoinSub($paymentTotals, 'selectable_order_payment_totals', function ($join): void {
                $join->on('selectable_order_payment_totals.order_id', '=', 'orders.id');
            })
            ->where(function ($query): void {
                $query->whereRaw('COALESCE(selectable_order_payment_totals.total, 0) <= 0')
                    ->orWhere(function ($query): void {
                        $query->whereRaw('COALESCE(selectable_order_payment_totals.total, 0) > 0')
                            ->whereColumn('selectable_order_payment_totals.total', '<', 'orders.total_cost');
                    });
            })
            ->latest('created_at')
            ->get(['orders.public_id', 'orders.order_number'])
            ->map(fn (Order $order): array => [
                'id' => $order->public_id,
                'number' => $order->order_number,
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'orders' => $orders,
        ]);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        [$data, $order] = $this->validatedPaymentData($request, $client);

        $payment = DB::transaction(function () use ($request, $client, $data, $order): ClientPayment {
            $this->ensureValidOverpaymentBalance($client, $data);
            $this->ensureOrderCanAcceptNewPayment($order);

            $payment = ClientPayment::query()->create([
                ...$data,
                'client_id' => $client->id,
                'order_id' => $order?->id,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncOrderPaymentTotals($order);

            return $payment;
        });

        return response()->json([
            'ok' => true,
            'payment_id' => $payment->public_id,
            'redirect_url' => $this->redirectUrl($request, $client, $order),
        ]);
    }

    public function update(Request $request, Client $client, ClientPayment $clientPayment): JsonResponse
    {
        abort_unless((int) $clientPayment->client_id === (int) $client->id, 404);

        $clientPayment->loadMissing('order');
        $previousOrder = $clientPayment->order;
        [$data, $order] = $this->validatedPaymentData($request, $client);
        $data['is_from_overpayment'] = $clientPayment->is_from_overpayment;
        if ($data['is_from_overpayment'] && $data['payment_type'] !== 'order') {
            throw ValidationException::withMessages([
                'payment_type' => 'Списання з переплати має бути прив’язане до замовлення.',
            ]);
        }

        DB::transaction(function () use ($request, $clientPayment, $data, $order, $previousOrder): void {
            $this->ensureValidOverpaymentBalance($clientPayment->client, $data, $clientPayment);
            if ((int) $previousOrder?->id !== (int) $order?->id) {
                $this->ensureOrderCanAcceptNewPayment($order);
            }

            $clientPayment->loadMissing('order:id,order_number');
            $before = $this->historySnapshot($clientPayment);

            $clientPayment->update([
                ...$data,
                'order_id' => $order?->id,
                'updated_by' => $request->user()?->id,
            ]);
            $clientPayment->setRelation('order', $order);
            $after = $this->historySnapshot($clientPayment);

            $fieldLabels = [
                'amount' => 'Сума',
                'currency' => 'Валюта',
                'paid_at' => 'Дата та час',
                'payment_type' => 'Тип платежу',
                'order' => 'Номер замовлення',
                'comment' => 'Коментар',
            ];
            $changes = [];

            foreach ($fieldLabels as $field => $label) {
                if (($before[$field] ?? null) === ($after[$field] ?? null)) {
                    continue;
                }

                $changes[] = [
                    'field' => $field,
                    'label' => $label,
                    'before' => $before[$field] ?? '—',
                    'after' => $after[$field] ?? '—',
                ];
            }

            if ($changes !== []) {
                $clientPayment->forceFill(['is_edited' => true])->saveQuietly();
                $clientPayment->histories()->create([
                    'user_id' => $request->user()?->id,
                    'changes' => $changes,
                ]);
            }

            $this->syncOrderPaymentTotals($previousOrder);
            if ((int) $previousOrder?->id !== (int) $order?->id) {
                $this->syncOrderPaymentTotals($order);
            }
        });

        return response()->json([
            'ok' => true,
            'payment_id' => $clientPayment->public_id,
            'redirect_url' => $this->redirectUrl($request, $client, $order),
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?Order}
     */
    private function validatedPaymentData(Request $request, Client $client): array
    {
        $today = now('Europe/Kiev')->toDateString();
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'in:UAH,USD,EUR'],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'payment_time' => ['required', 'date_format:H:i'],
            'payment_type' => ['required', 'string', 'in:prepayment,order'],
            'payment_source' => ['nullable', 'string', 'in:direct,overpayment'],
            'order_public_id' => ['nullable', 'required_if:payment_type,order', 'uuid'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'return_context' => ['nullable', 'string', 'in:client,order'],
        ]);

        $order = null;
        if ($validated['payment_type'] === 'order') {
            $order = Order::query()
                ->where('client_id', $client->id)
                ->where('public_id', $validated['order_public_id'])
                ->first();

            if (! $order) {
                abort(422, 'Оберіть замовлення, що належить цьому клієнту.');
            }
        }

        $isFromOverpayment = ($validated['payment_source'] ?? 'direct') === 'overpayment';
        if ($isFromOverpayment && $validated['payment_type'] !== 'order') {
            throw ValidationException::withMessages([
                'payment_type' => 'Списання з переплати має бути прив’язане до замовлення.',
            ]);
        }

        $paidAt = CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            $validated['payment_date'].' '.$validated['payment_time'],
            'Europe/Kiev'
        )->utc();

        return [[
            'amount' => (int) $validated['amount'],
            'currency' => $validated['currency'],
            'payment_type' => $validated['payment_type'],
            'is_from_overpayment' => $isFromOverpayment,
            'paid_at' => $paidAt,
            'comment' => filled($validated['comment'] ?? null) ? trim($validated['comment']) : null,
        ], $order];
    }

    /** @return array<string, int|string> */
    private function historySnapshot(ClientPayment $payment): array
    {
        return [
            'amount' => (int) $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paid_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i'),
            'payment_type' => $payment->payment_type === 'order' ? 'Оплата замовлення' : 'Переплата',
            'order' => $payment->order?->order_number ?? '—',
            'comment' => $payment->comment ?: '—',
        ];
    }

    private function redirectUrl(Request $request, Client $client, ?Order $order): string
    {
        if ($request->input('return_context') === 'order' && $order) {
            return route('orders.show', [
                'order' => $order,
                'payments' => 1,
            ]);
        }

        return route('orders.clients.edit', [
            'client' => $client,
            'section' => 'payments',
        ]);
    }

    private function syncOrderPaymentTotals(?Order $order): void
    {
        if (! $order) {
            return;
        }

        $paymentsTotal = (int) ClientPayment::query()
            ->where('order_id', $order->id)
            ->sum('amount');

        $order->forceFill([
            'payments_total' => $paymentsTotal,
            'amount_due' => (float) $order->total_cost - $paymentsTotal,
        ])->saveQuietly();
    }

    /** @param array<string, mixed> $data */
    private function ensureValidOverpaymentBalance(Client $client, array $data, ?ClientPayment $currentPayment = null): void
    {
        Client::query()->whereKey($client->id)->lockForUpdate()->firstOrFail();

        $prepaymentTotal = (int) ClientPayment::query()
            ->where('client_id', $client->id)
            ->where('payment_type', 'prepayment')
            ->sum('amount');
        $usedOverpaymentTotal = (int) ClientPayment::query()
            ->where('client_id', $client->id)
            ->where('is_from_overpayment', true)
            ->sum('amount');

        if ($currentPayment) {
            if ($currentPayment->payment_type === 'prepayment') {
                $prepaymentTotal -= (int) $currentPayment->amount;
            }
            if ($currentPayment->is_from_overpayment) {
                $usedOverpaymentTotal -= (int) $currentPayment->amount;
            }
        }

        if ($data['payment_type'] === 'prepayment') {
            $prepaymentTotal += (int) $data['amount'];
        }
        if ($data['is_from_overpayment']) {
            $usedOverpaymentTotal += (int) $data['amount'];
        }

        if ($usedOverpaymentTotal > $prepaymentTotal) {
            throw ValidationException::withMessages([
                'amount' => 'Сума списання перевищує доступну переплату клієнта.',
            ]);
        }
    }

    private function ensureOrderCanAcceptNewPayment(?Order $order): void
    {
        if (! $order) {
            return;
        }

        $paymentsTotal = (int) ClientPayment::query()
            ->where('order_id', $order->id)
            ->sum('amount');

        if ($paymentsTotal > 0 && $paymentsTotal >= (float) $order->total_cost) {
            throw ValidationException::withMessages([
                'order_public_id' => 'Неможливо додати платіж до вже сплаченого замовлення або замовлення з переплатою.',
            ]);
        }
    }
}
