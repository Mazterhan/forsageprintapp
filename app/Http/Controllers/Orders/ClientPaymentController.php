<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Services\PermissionService;
use App\Services\PrivatBankExchangeRateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ClientPaymentController extends Controller
{
    public function rates(Request $request, PrivatBankExchangeRateService $exchangeRates, PermissionService $permissions): JsonResponse
    {
        abort_unless(
            $permissions->can($request->user(), 'orders_payments')
            || $permissions->can($request->user(), 'orders_clients_payments'),
            403
        );

        try {
            $payload = $exchangeRates->currentSaleRates();
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json([
            'ok' => true,
            ...$payload,
        ]);
    }

    public function orders(Request $request, Client $client, PermissionService $permissions): JsonResponse
    {
        abort_unless($permissions->can($request->user(), 'orders_clients_payments'), 403);

        $paymentTotals = DB::table('client_payments')
            ->selectRaw('order_id, SUM(amount_uah) as total')
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
            ->get([
                'orders.public_id',
                'orders.order_number',
                'orders.total_cost',
                DB::raw('COALESCE(selectable_order_payment_totals.total, 0) as linked_payments_total'),
            ])
            ->map(fn (Order $order): array => [
                'id' => $order->public_id,
                'number' => $order->order_number,
                'amountDue' => max(0, (int) round((float) $order->total_cost - (float) $order->linked_payments_total)),
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'orders' => $orders,
        ]);
    }

    public function store(
        Request $request,
        Client $client,
        PrivatBankExchangeRateService $exchangeRates,
        PermissionService $permissions
    ): JsonResponse
    {
        $this->authorizePaymentContext($request, $permissions);
        [$data, $order] = $this->validatedPaymentData($request, $client, $exchangeRates);
        $this->authorizeOrderContextScope($request, $permissions, $order);

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
            'notification' => $this->currencyNotification($payment),
        ]);
    }

    public function update(
        Request $request,
        Client $client,
        ClientPayment $clientPayment,
        PrivatBankExchangeRateService $exchangeRates,
        PermissionService $permissions
    ): JsonResponse
    {
        $this->authorizePaymentContext($request, $permissions);
        abort_unless((int) $clientPayment->client_id === (int) $client->id, 404);

        if ($request->input('return_context') === 'order') {
            abort_unless($clientPayment->order_id !== null, 403);
            $this->authorizeOrderContextScope($request, $permissions, $clientPayment->order);
        }

        $clientPayment->loadMissing('order');
        $previousOrder = $clientPayment->order;
        [$data, $order] = $this->validatedPaymentData($request, $client, $exchangeRates, $clientPayment);
        $this->authorizeOrderContextScope($request, $permissions, $order);
        $data['is_from_overpayment'] = $clientPayment->is_from_overpayment;
        if ($data['is_from_overpayment'] && ! in_array($data['payment_type'], ['order', 'writeoff'], true)) {
            throw ValidationException::withMessages([
                'payment_type' => 'Оберіть внесення платежу за замовлення або просте списання.',
            ]);
        }
        if ($data['is_from_overpayment'] && $data['currency'] !== 'UAH') {
            throw ValidationException::withMessages([
                'currency' => 'Списання з переплати доступне лише у гривні.',
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
                'amount' => 'Сума операції',
                'currency' => 'Валюта',
                'exchange_rate' => 'Курс SALE ПриватБанку',
                'exchange_rate_type' => 'Тип курсу',
                'exchange_rate_source' => 'Джерело курсу',
                'exchange_rate_fetched_at' => 'Час отримання курсу',
                'calculated_amount_uah' => 'Автоматично розрахована сума (ГРН)',
                'amount_uah' => 'Сума списання (ГРН)',
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
            'notification' => $this->currencyNotification($clientPayment->fresh()),
        ]);
    }

    private function authorizePaymentContext(Request $request, PermissionService $permissions): void
    {
        $permission = $request->input('return_context') === 'order'
            ? 'orders_payments'
            : 'orders_clients_payments';

        abort_unless($permissions->can($request->user(), $permission), 403);
    }

    private function authorizeOrderContextScope(
        Request $request,
        PermissionService $permissions,
        ?Order $order
    ): void {
        if ($request->input('return_context') !== 'order' || ! $order) {
            return;
        }

        abort_unless($permissions->can($request->user(), 'orders_access'), 403);

        if ($permissions->orderScope($request->user()) === 'own') {
            abort_unless((int) $order->created_by === (int) $request->user()?->id, 403);
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?Order}
     */
    private function validatedPaymentData(
        Request $request,
        Client $client,
        PrivatBankExchangeRateService $exchangeRates,
        ?ClientPayment $currentPayment = null
    ): array {
        $today = now('Europe/Kiev')->toDateString();
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'amount_uah' => ['nullable', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'in:UAH,USD,EUR'],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'payment_time' => ['required', 'date_format:H:i'],
            'payment_type' => ['required', 'string', 'in:prepayment,order,writeoff'],
            'payment_source' => ['nullable', 'string', 'in:direct,overpayment'],
            'order_public_id' => ['nullable', 'required_if:payment_type,order', 'uuid'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'return_context' => ['nullable', 'string', 'in:client,order'],
        ]);

        if (($validated['return_context'] ?? null) === 'order' && $validated['payment_type'] !== 'order') {
            throw ValidationException::withMessages([
                'payment_type' => 'У картці замовлення можна керувати лише платежами, прив’язаними до цього замовлення.',
            ]);
        }

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
        if ($isFromOverpayment && ! in_array($validated['payment_type'], ['order', 'writeoff'], true)) {
            throw ValidationException::withMessages([
                'payment_type' => 'Оберіть внесення платежу за замовлення або просте списання.',
            ]);
        }

        if ($validated['payment_type'] === 'writeoff' && ! $isFromOverpayment) {
            throw ValidationException::withMessages([
                'payment_type' => 'Просте списання доступне лише для списання з переплати.',
            ]);
        }

        if ($isFromOverpayment && $validated['currency'] !== 'UAH') {
            throw ValidationException::withMessages([
                'currency' => 'Списання з переплати доступне лише у гривні.',
            ]);
        }

        $amount = (int) $validated['amount'];
        $currency = $validated['currency'];
        $amountUah = $amount;
        $calculatedAmountUah = null;
        $exchangeRate = null;
        $exchangeRateType = null;
        $exchangeRateSource = null;
        $exchangeRateFetchedAt = null;

        if ($currency !== 'UAH') {
            $sameOriginalPayment = $currentPayment
                && $currentPayment->currency === $currency
                && (int) $currentPayment->amount === $amount
                && $currentPayment->exchange_rate !== null;

            if ($sameOriginalPayment) {
                $exchangeRate = (float) $currentPayment->exchange_rate;
                $exchangeRateType = $currentPayment->exchange_rate_type;
                $exchangeRateSource = $currentPayment->exchange_rate_source;
                $exchangeRateFetchedAt = $currentPayment->exchange_rate_fetched_at;
            } else {
                try {
                    $quote = $exchangeRates->quote($currency);
                } catch (RuntimeException $exception) {
                    throw ValidationException::withMessages([
                        'currency' => $exception->getMessage(),
                    ]);
                }

                $exchangeRate = $quote['rate'];
                $exchangeRateType = $quote['type'];
                $exchangeRateSource = $quote['source'];
                $exchangeRateFetchedAt = $quote['fetched_at'];
            }

            $calculatedAmountUah = (int) ceil($amount * $exchangeRate);
            $amountUah = (int) ($validated['amount_uah'] ?? 0);
            if ($amountUah <= 0) {
                throw ValidationException::withMessages([
                    'amount_uah' => 'Вкажіть суму списання у гривні цілим числом більше нуля.',
                ]);
            }
        }

        $paidAt = CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            $validated['payment_date'].' '.$validated['payment_time'],
            'Europe/Kiev'
        )->utc();

        $comment = filled($validated['comment'] ?? null) ? trim($validated['comment']) : null;
        if ($validated['payment_type'] === 'writeoff') {
            preg_match_all('/[\p{L}\p{N}]/u', (string) $comment, $commentCharacters);
            if (count($commentCharacters[0] ?? []) < 20) {
                throw ValidationException::withMessages([
                    'comment' => 'Для простого списання коментар має містити щонайменше 20 букв або цифр.',
                ]);
            }
        }
        if ($currency !== 'UAH' && $amountUah !== $calculatedAmountUah) {
            $note = $this->manualConversionComment(
                $currency,
                $amount,
                $exchangeRate,
                $calculatedAmountUah,
                $amountUah,
                $exchangeRateFetchedAt
            );
            if (! str_contains((string) $comment, $note)) {
                $comment = filled($comment) ? $comment."\n\n".$note : $note;
            }
        }

        return [[
            'amount' => $amount,
            'amount_uah' => $amountUah,
            'calculated_amount_uah' => $calculatedAmountUah,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'exchange_rate_type' => $exchangeRateType,
            'exchange_rate_source' => $exchangeRateSource,
            'exchange_rate_fetched_at' => $exchangeRateFetchedAt,
            'payment_type' => $validated['payment_type'],
            'is_from_overpayment' => $isFromOverpayment,
            'paid_at' => $paidAt,
            'comment' => $comment,
        ], $order];
    }

    /** @return array<string, int|string> */
    private function historySnapshot(ClientPayment $payment): array
    {
        return [
            'amount' => (int) $payment->amount,
            'currency' => $payment->currency,
            'exchange_rate' => $payment->exchange_rate !== null ? number_format((float) $payment->exchange_rate, 6, '.', '') : '—',
            'exchange_rate_type' => $payment->exchange_rate_type ?? '—',
            'exchange_rate_source' => $payment->exchange_rate_source ?? '—',
            'exchange_rate_fetched_at' => $payment->exchange_rate_fetched_at?->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i') ?? '—',
            'calculated_amount_uah' => $payment->calculated_amount_uah ?? '—',
            'amount_uah' => (int) $payment->amount_uah,
            'paid_at' => $payment->paid_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i'),
            'payment_type' => match ($payment->payment_type) {
                'order' => 'Оплата замовлення',
                'writeoff' => 'Просте списання',
                default => 'Переплата',
            },
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
            ->sum('amount_uah');

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
            ->sum('amount_uah');
        $usedOverpaymentTotal = (int) ClientPayment::query()
            ->where('client_id', $client->id)
            ->where('is_from_overpayment', true)
            ->sum('amount_uah');

        if ($currentPayment) {
            if ($currentPayment->payment_type === 'prepayment') {
                $prepaymentTotal -= (int) $currentPayment->amount_uah;
            }
            if ($currentPayment->is_from_overpayment) {
                $usedOverpaymentTotal -= (int) $currentPayment->amount_uah;
            }
        }

        if ($data['payment_type'] === 'prepayment') {
            $prepaymentTotal += (int) $data['amount_uah'];
        }
        if ($data['is_from_overpayment']) {
            $usedOverpaymentTotal += (int) $data['amount_uah'];
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
            ->sum('amount_uah');

        if ($paymentsTotal > 0 && $paymentsTotal >= (float) $order->total_cost) {
            throw ValidationException::withMessages([
                'order_public_id' => 'Неможливо додати платіж до вже сплаченого замовлення або замовлення з переплатою.',
            ]);
        }
    }

    private function manualConversionComment(
        string $currency,
        int $amount,
        float $rate,
        int $calculatedAmountUah,
        int $amountUah,
        mixed $fetchedAt
    ): string {
        $currencyName = $currency === 'USD' ? 'доларів' : 'євро';
        $rateDate = $fetchedAt
            ? CarbonImmutable::parse($fetchedAt)->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : now('Europe/Kiev')->format('d.m.Y H:i');

        return sprintf(
            'Курс SALE ПриватБанку на %s становив %s грн/%s. Автоматично розрахована сума у полі «Сума списання (ГРН)» за %d %s була %s грн. Користувачем встановлено суму %s грн.',
            $rateDate,
            number_format($rate, 6, ',', ' '),
            $currency,
            $amount,
            $currencyName,
            number_format($calculatedAmountUah, 0, ',', ' '),
            number_format($amountUah, 0, ',', ' '),
        );
    }

    private function currencyNotification(ClientPayment $payment): ?string
    {
        if ($payment->currency === 'UAH') {
            return null;
        }

        $amountUahLabel = $payment->payment_type === 'prepayment'
            ? 'Сума поповнення переплати'
            : 'Сума списання';

        return sprintf(
            'Валютний платіж збережено. Курс SALE ПриватБанку: %s грн/%s. Сума операції: %d %s. %s: %s грн.',
            number_format((float) $payment->exchange_rate, 6, ',', ' '),
            $payment->currency,
            (int) $payment->amount,
            $payment->currency,
            $amountUahLabel,
            number_format((int) $payment->amount_uah, 0, ',', ' '),
        );
    }
}
