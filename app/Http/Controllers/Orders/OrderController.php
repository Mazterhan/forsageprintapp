<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\OrderProposal;
use App\Models\OrderProposalEditLock;
use App\Models\PriceItem;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductTypeCategoryRule;
use App\Models\User;
use App\Services\PermissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request, PermissionService $permissions)
    {
        $canAccessOrders = $permissions->can($request->user(), 'orders_access');
        $sort = (string) $request->query('sort', 'date');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPageRaw = strtolower((string) $request->query('per_page', '20'));
        $perPageRaw = in_array($perPageRaw, ['20', '50', '100', 'all'], true) ? $perPageRaw : '20';

        $sortMap = [
            'status' => 'orders.status',
            'number' => 'orders.order_number',
            'customer' => 'orders.customer_name',
            'user' => 'users.name',
            'amount_due' => 'orders.amount_due',
            'total_cost' => 'orders.total_cost',
        ];

        $paymentStatusLabels = [
            'unpaid' => 'Не сплачено',
            'partial' => 'Частково сплачено',
            'paid' => 'Сплачено',
            'overpaid' => 'Є переплата',
        ];
        $paymentStatusSql = "CASE
            WHEN COALESCE(order_payment_totals.total, 0) <= 0 THEN 'unpaid'
            WHEN COALESCE(order_payment_totals.total, 0) < orders.total_cost THEN 'partial'
            WHEN COALESCE(order_payment_totals.total, 0) = orders.total_cost THEN 'paid'
            ELSE 'overpaid'
        END";
        $selectedPaymentStatuses = array_values(array_intersect(
            array_keys($paymentStatusLabels),
            array_map('strval', (array) $request->query('payment_status', []))
        ));
        $selectedClientIds = array_values(array_unique(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $request->query('client_id', [])
        ))));
        $selectedUserIds = array_values(array_unique(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $request->query('user_id', [])
        ))));
        $selectedOrderStatuses = array_values(array_intersect(
            array_keys(Order::STATUSES),
            array_map('strval', (array) $request->query('order_status', []))
        ));

        $paymentTotals = DB::table('client_payments')
            ->selectRaw('order_id, SUM(amount_uah) as total')
            ->whereNotNull('order_id')
            ->groupBy('order_id');

        $applyOrderScope = function ($query) use ($canAccessOrders, $permissions, $request): void {
            if (! $canAccessOrders) {
                $query->whereRaw('1 = 0');
            } elseif ($permissions->orderScope($request->user()) === 'own') {
                $query->where('orders.created_by', $request->user()?->id);
            }
        };

        $availableOrdersQuery = Order::query();
        $applyOrderScope($availableOrdersQuery);

        $availableClients = Client::query()
            ->whereIn('id', (clone $availableOrdersQuery)
                ->whereNotNull('orders.client_id')
                ->select('orders.client_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $availableUsers = User::query()
            ->whereIn('id', (clone $availableOrdersQuery)
                ->whereNotNull('orders.last_edited_by')
                ->select('orders.last_edited_by'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $existingOrderStatuses = (clone $availableOrdersQuery)
            ->whereNotNull('orders.status')
            ->distinct()
            ->pluck('orders.status')
            ->all();
        $availableOrderStatuses = array_intersect_key(Order::STATUSES, array_flip($existingOrderStatuses));

        $availablePaymentStatusKeys = (clone $availableOrdersQuery)
            ->leftJoinSub(clone $paymentTotals, 'order_payment_totals', function ($join): void {
                $join->on('order_payment_totals.order_id', '=', 'orders.id');
            })
            ->selectRaw("{$paymentStatusSql} as payment_filter")
            ->distinct()
            ->pluck('payment_filter')
            ->all();
        $availablePaymentStatuses = array_intersect_key($paymentStatusLabels, array_flip($availablePaymentStatusKeys));

        $query = Order::query()
            ->leftJoin('users', 'users.id', '=', 'orders.last_edited_by')
            ->leftJoinSub($paymentTotals, 'order_payment_totals', function ($join): void {
                $join->on('order_payment_totals.order_id', '=', 'orders.id');
            })
            ->select('orders.*')
            ->addSelect(DB::raw('COALESCE(order_payment_totals.total, 0) as linked_payments_total'))
            ->with('lastEditedBy:id,name');

        $applyOrderScope($query);

        if ($selectedPaymentStatuses !== []) {
            $query->whereIn(DB::raw($paymentStatusSql), $selectedPaymentStatuses);
        }
        if ($selectedClientIds !== []) {
            $query->whereIn('orders.client_id', $selectedClientIds);
        }
        if ($selectedUserIds !== []) {
            $query->whereIn('orders.last_edited_by', $selectedUserIds);
        }
        if ($selectedOrderStatuses !== []) {
            $query->whereIn('orders.status', $selectedOrderStatuses);
        }

        if ($sort === 'date') {
            $query->orderBy('orders.updated_at', $direction);
        } elseif ($sort === 'payment') {
            $query->orderByRaw("CASE
                WHEN COALESCE(order_payment_totals.total, 0) <= 0 THEN 1
                WHEN COALESCE(order_payment_totals.total, 0) < orders.total_cost THEN 2
                WHEN COALESCE(order_payment_totals.total, 0) = orders.total_cost THEN 3
                ELSE 4
            END {$direction}");
        } elseif (isset($sortMap[$sort])) {
            $query->orderBy($sortMap[$sort], $direction);
        } else {
            $sort = 'date';
            $query->orderBy('orders.updated_at', 'desc');
        }

        $perPage = match ($perPageRaw) {
            '50' => 50,
            '100' => 100,
            'all' => max(1, (clone $query)->count()),
            default => 20,
        };

        return view('orders.index', [
            'orders' => $query->paginate($perPage)->withQueryString(),
            'sort' => $sort,
            'direction' => $direction,
            'perPageRaw' => $perPageRaw,
            'orderFilters' => [
                'payment_status' => $selectedPaymentStatuses,
                'client_id' => $selectedClientIds,
                'user_id' => $selectedUserIds,
                'order_status' => $selectedOrderStatuses,
            ],
            'availablePaymentStatuses' => $availablePaymentStatuses,
            'availableClients' => $availableClients,
            'availableUsers' => $availableUsers,
            'availableOrderStatuses' => $availableOrderStatuses,
            'ordersPermissions' => [
                'calculation' => $permissions->can($request->user(), 'orders_calculation'),
                'proposals' => $permissions->can($request->user(), 'orders_proposals'),
                'access' => $canAccessOrders,
                'clients' => $permissions->can($request->user(), 'orders_clients_manage'),
            ],
        ]);
    }

    public function create(Request $request, PermissionService $permissions)
    {
        abort_unless($permissions->can($request->user(), 'orders_access'), 403);

        $clients = Client::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('orders.create', [
            'clients' => $clients,
        ]);
    }

    public function appendCandidate(Request $request, PermissionService $permissions): JsonResponse
    {
        abort_unless($permissions->can($request->user(), 'orders_access'), 403);

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
        ]);

        $query = Order::query()
            ->where('client_id', (int) $data['client_id'])
            ->where('status', Order::STATUS_NEW)
            ->whereRaw('(SELECT COALESCE(SUM(client_payments.amount_uah), 0) FROM client_payments WHERE client_payments.order_id = orders.id) <= 0');

        if ($permissions->orderScope($request->user()) === 'own') {
            $query->where('created_by', $request->user()?->id);
        }

        $order = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'ok' => true,
            'order' => $order ? [
                'id' => $order->public_id,
                'number' => $order->order_number,
                'append_url' => route('orders.append-items', $order),
            ] : null,
        ]);
    }

    public function appendItems(Request $request, Order $order, PermissionService $permissions): JsonResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.nomenclature' => ['required', 'string', 'max:500'],
            'items.*.description' => ['nullable', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'integer', 'min:1'],
        ]);

        $newItems = collect($data['items'])->map(function (array $item): array {
            $quantity = (int) $item['quantity'];
            $unitCost = (int) $item['unit_cost'];

            return [
                'item_id' => (string) Str::uuid(),
                'nomenclature' => trim((string) $item['nomenclature']),
                'description' => trim((string) ($item['description'] ?? '')),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'sum' => $quantity * $unitCost,
            ];
        })->values()->all();

        $updatedOrder = DB::transaction(function () use ($request, $order, $newItems): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $paymentsTotal = (float) $lockedOrder->payments()->sum('amount_uah');

            if ($lockedOrder->status !== Order::STATUS_NEW || $paymentsTotal > 0) {
                abort(409, 'Замовлення вже не відповідає умовам додавання позицій.');
            }

            $lockedOrder->ensureItemIds();
            $items = array_merge(is_array($lockedOrder->items) ? $lockedOrder->items : [], $newItems);
            $totalCost = (float) collect($items)->sum('sum');
            $lockedOrder->update([
                'items' => $items,
                'payments_total' => $paymentsTotal,
                'amount_due' => $totalCost - $paymentsTotal,
                'total_cost' => $totalCost,
                'last_edited_by' => $request->user()?->id,
            ]);
            $lockedOrder->client?->update([
                'last_order_at' => now(),
                'updated_by' => $request->user()?->id,
            ]);

            return $lockedOrder;
        });

        return response()->json([
            'ok' => true,
            'order_id' => $updatedOrder->public_id,
            'order_number' => $updatedOrder->order_number,
            'redirect_url' => route('orders.show', $updatedOrder),
        ]);
    }

    public function store(Request $request, PermissionService $permissions): JsonResponse
    {
        abort_unless($permissions->can($request->user(), 'orders_access'), 403);

        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'create_client' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nomenclature' => ['required', 'string', 'max:500'],
            'items.*.description' => ['nullable', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'integer', 'min:1'],
        ]);

        $customerName = trim((string) $data['customer_name']);
        $client = ! empty($data['client_id'])
            ? Client::query()->find((int) $data['client_id'])
            : $this->findClientByName($customerName);

        if (! $client && ! $request->boolean('create_client')) {
            return response()->json([
                'ok' => false,
                'code' => 'client_not_found',
                'message' => 'Замовника з таким ім\'ям не знайдено.',
                'customer_name' => $customerName,
            ], 422);
        }

        $normalizedItems = collect($data['items'])
            ->map(function (array $item): array {
                $quantity = (int) $item['quantity'];
                $unitCost = (int) $item['unit_cost'];

                return [
                    'item_id' => (string) Str::uuid(),
                    'nomenclature' => trim((string) $item['nomenclature']),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'sum' => $quantity * $unitCost,
                ];
            })
            ->values()
            ->all();

        $totalCost = (float) collect($normalizedItems)->sum('sum');

        $order = DB::transaction(function () use ($request, $client, $customerName, $normalizedItems, $totalCost): Order {
            if (! $client) {
                $client = $this->findClientByName($customerName, true);
            }

            if (! $client) {
                $temporaryCode = 'FP-TEMP-'.Str::upper(Str::random(8));
                $client = Client::query()->create([
                    'code' => $temporaryCode,
                    'name' => $customerName,
                    'status' => 'active',
                    'created_by' => $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ]);
                $client->update([
                    'code' => 'FP-'.str_pad((string) $client->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $order = Order::query()->create([
                'status' => Order::STATUS_NEW,
                'customer_name' => $client->name,
                'client_id' => $client->id,
                'created_by' => $request->user()?->id,
                'last_edited_by' => $request->user()?->id,
                'items' => $normalizedItems,
                'payments_total' => 0,
                'amount_due' => $totalCost,
                'total_cost' => $totalCost,
            ]);

            $client->update([
                'last_order_at' => now(),
                'updated_by' => $request->user()?->id,
            ]);

            return $order;
        });

        return response()->json([
            'ok' => true,
            'order_id' => $order->public_id,
            'order_number' => $order->order_number,
            'redirect_url' => route('orders.index'),
        ]);
    }

    public function show(Request $request, Order $order, PermissionService $permissions)
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        $canUpdateOrder = $permissions->can($request->user(), 'orders_update');
        $canManageOrderPayments = $permissions->can($request->user(), 'orders_payments');
        $canSpendOrderOverpayment = $permissions->can($request->user(), 'orders_payments_overpayment');
        $canEditOrderPayments = $permissions->can($request->user(), 'orders_payments_edit');

        $order->load([
            'client:id,public_id,name',
            'lastEditedBy:id,name',
        ]);

        if ($canManageOrderPayments) {
            $order->load([
                'payments' => fn ($query) => $query->latest('paid_at'),
                'payments.createdBy:id,name',
                'payments.histories.user:id,name',
                'payments.automaticOverpayment:id,public_id,source_payment_id,amount_uah',
            ]);
        }

        $paymentModalData = $canManageOrderPayments ? $order->payments->map(function ($payment) use ($order, $canEditOrderPayments, $canSpendOrderOverpayment): array {
            return [
                'id' => $payment->public_id,
                'amount' => $payment->amount,
                'amountUah' => (int) $payment->amount_uah + (int) ($payment->automaticOverpayment?->amount_uah ?? 0),
                'calculatedAmountUah' => $payment->calculated_amount_uah,
                'currency' => $payment->currency,
                'exchangeRate' => $payment->exchange_rate,
                'exchangeRateType' => $payment->exchange_rate_type,
                'exchangeRateSource' => $payment->exchange_rate_source,
                'exchangeRateFetchedAt' => $payment->exchange_rate_fetched_at?->toIso8601String(),
                'date' => $payment->paid_at->copy()->timezone('Europe/Kiev')->format('Y-m-d'),
                'time' => $payment->paid_at->copy()->timezone('Europe/Kiev')->format('H:i'),
                'comment' => $payment->comment ?? '',
                'fromOverpayment' => $payment->is_from_overpayment,
                'canEdit' => $canEditOrderPayments && (! $payment->is_from_overpayment || $canSpendOrderOverpayment),
                'automaticOverpaymentId' => $payment->automaticOverpayment?->public_id,
                'updateUrl' => route('orders.clients.payments.update', [$order->client, $payment]),
                'histories' => $payment->histories->map(fn ($history): array => [
                    'date' => $history->created_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i'),
                    'user' => $history->user?->name ?? '—',
                    'changes' => $history->changes,
                ])->values()->all(),
            ];
        })->values() : collect();
        $clientOverpaymentTotal = $canSpendOrderOverpayment && $order->client_id
            ? (int) ClientPayment::query()
                ->where('client_id', $order->client_id)
                ->where('payment_type', 'prepayment')
                ->sum('amount_uah')
                - (int) ClientPayment::query()
                    ->where('client_id', $order->client_id)
                    ->where('is_from_overpayment', true)
                    ->sum('amount_uah')
            : 0;
        $orderPaymentsTotal = (float) $order->payments()->sum('amount_uah');
        $canAddOrderPayment = $orderPaymentsTotal <= 0 || $orderPaymentsTotal < (float) $order->total_cost;
        $canMergeOrder = $canUpdateOrder
            && $order->client_id
            && $order->status === Order::STATUS_NEW
            && $orderPaymentsTotal <= 0;

        return view('orders.show', [
            'order' => $order,
            'paymentModalData' => $paymentModalData,
            'clientOverpaymentTotal' => max(0, $clientOverpaymentTotal),
            'orderPaymentsTotal' => $orderPaymentsTotal,
            'canAddOrderPayment' => $canAddOrderPayment,
            'orderPermissions' => [
                'update' => $canUpdateOrder,
                'merge' => $canMergeOrder,
                'payments' => $canManageOrderPayments,
                'payments_overpayment' => $canSpendOrderOverpayment,
                'payments_edit' => $canEditOrderPayments,
            ],
        ]);
    }

    public function history(Request $request, Order $order, PermissionService $permissions): JsonResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        abort_unless($permissions->can($request->user(), 'orders_update'), 403);

        $histories = $order->histories()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'ok' => true,
            'count' => $histories->count(),
            'html' => view('orders.partials.history-table', [
                'histories' => $histories,
            ])->render(),
        ]);
    }

    public function mergeCandidates(Request $request, Order $order, PermissionService $permissions): JsonResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        abort_unless($permissions->can($request->user(), 'orders_update'), 403);
        abort_if(! $order->client_id || $order->status !== Order::STATUS_NEW || $order->payments()->sum('amount_uah') > 0, 409);

        $query = Order::query()
            ->where('client_id', $order->client_id)
            ->whereKeyNot($order->id)
            ->where('status', Order::STATUS_NEW)
            ->whereRaw('(SELECT COALESCE(SUM(client_payments.amount_uah), 0) FROM client_payments WHERE client_payments.order_id = orders.id) <= 0')
            ->with('createdBy:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($permissions->orderScope($request->user()) === 'own') {
            $query->where('created_by', $request->user()?->id);
        }

        return response()->json([
            'ok' => true,
            'orders' => $query->get()->map(fn (Order $candidate): array => [
                'id' => $candidate->public_id,
                'number' => $candidate->order_number,
                'date' => $candidate->created_at?->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i') ?? '—',
                'user' => $candidate->createdBy?->name ?? '—',
                'amount_due' => (float) $candidate->amount_due,
            ])->values(),
        ]);
    }

    public function merge(Request $request, Order $order, PermissionService $permissions): JsonResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        abort_unless($permissions->can($request->user(), 'orders_update'), 403);

        $data = $request->validate([
            'target_order_id' => ['required', 'uuid'],
        ]);
        $targetOrder = Order::query()->where('public_id', $data['target_order_id'])->firstOrFail();
        $this->authorizeOrderAccess($request, $permissions, $targetOrder);

        $mergedOrder = DB::transaction(function () use ($request, $order, $targetOrder): Order {
            $lockedOrders = Order::query()
                ->whereIn('id', [$order->id, $targetOrder->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $source = $lockedOrders->get($order->id);
            $target = $lockedOrders->get($targetOrder->id);

            abort_if(! $source || ! $target || $source->id === $target->id, 422, 'Оберіть інше замовлення для об’єднання.');
            abort_if((int) $source->client_id !== (int) $target->client_id, 422, 'Замовлення належать різним клієнтам.');

            $sourcePayments = (float) $source->payments()->sum('amount_uah');
            $targetPayments = (float) $target->payments()->sum('amount_uah');
            abort_if(
                $source->status !== Order::STATUS_NEW
                || $target->status !== Order::STATUS_NEW
                || $sourcePayments > 0
                || $targetPayments > 0,
                409,
                'Одне із замовлень вже не відповідає умовам об’єднання.'
            );

            $source->ensureItemIds();
            $target->ensureItemIds();
            $sourceItems = is_array($source->items) ? $source->items : [];
            $targetItems = array_merge(is_array($target->items) ? $target->items : [], $sourceItems);
            $targetTotal = (float) collect($targetItems)->sum('sum');

            $target->update([
                'items' => $targetItems,
                'payments_total' => $targetPayments,
                'amount_due' => $targetTotal - $targetPayments,
                'total_cost' => $targetTotal,
                'last_edited_by' => $request->user()?->id,
            ]);

            $oldSourceStatus = (string) $source->status;
            $source->update([
                'status' => Order::STATUS_CANCELLED,
                'items' => [],
                'payments_total' => $sourcePayments,
                'amount_due' => 0,
                'total_cost' => 0,
                'last_edited_by' => $request->user()?->id,
            ]);
            $this->recordOrderStatusChange($source, $oldSourceStatus, Order::STATUS_CANCELLED, $request->user()?->id);

            return $target;
        });

        return response()->json([
            'ok' => true,
            'order_id' => $mergedOrder->public_id,
            'order_number' => $mergedOrder->order_number,
            'redirect_url' => route('orders.show', $mergedOrder),
        ]);
    }

    public function updateStatus(Request $request, Order $order, PermissionService $permissions): JsonResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        abort_unless($permissions->can($request->user(), 'orders_update'), 403);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Order::STATUSES))],
        ]);
        $newStatus = (string) $data['status'];
        $oldStatus = (string) ($order->status ?: Order::STATUS_NEW);

        if ($newStatus !== $oldStatus) {
            DB::transaction(function () use ($request, $order, $oldStatus, $newStatus): void {
                $order->update([
                    'status' => $newStatus,
                    'last_edited_by' => $request->user()?->id,
                ]);
                $this->recordOrderStatusChange($order, $oldStatus, $newStatus, $request->user()?->id);
            });
        }

        return response()->json([
            'ok' => true,
            'status' => $order->fresh()->status,
            'status_label' => $order->fresh()->statusLabel(),
        ]);
    }

    public function downloadPdf(Request $request, Order $order, PermissionService $permissions): Response
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        $order->load('client:id,name');

        $paymentsTotal = (int) $order->payments()->sum('amount_uah');
        $totalCost = (int) round((float) $order->total_cost);
        $filename = 'Замовлення-'.$order->order_number.'.pdf';

        $response = Pdf::loadView('orders.pdf', [
            'order' => $order,
            'items' => is_array($order->items) ? $order->items : [],
            'paymentsTotal' => $paymentsTotal,
            'amountDue' => $totalCost - $paymentsTotal,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);

        $response->headers->set(
            'Content-Disposition',
            sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                $filename,
                rawurlencode($filename)
            )
        );

        return $response;
    }

    public function downloadExcel(Request $request, Order $order, PermissionService $permissions): StreamedResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        $order->load('client:id,name');

        $items = is_array($order->items) ? $order->items : [];
        $paymentsTotal = (int) $order->payments()->sum('amount_uah');
        $totalCost = (int) round((float) $order->total_cost);
        $amountDue = $totalCost - $paymentsTotal;
        $filename = 'Замовлення-'.$order->order_number.'.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Замовлення');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Замовлення '.$order->order_number);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A1:F1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->fromArray([
            ['Виконавець замовлення:', 'Форсаж Прінт'],
            ['Замовник:', $order->client?->name ?? $order->customer_name],
            ['Дата замовлення:', $order->created_at?->copy()->timezone('Europe/Kiev')->format('d.m.Y') ?? '—'],
        ], null, 'A3');
        $sheet->mergeCells('B3:F3');
        $sheet->mergeCells('B4:F4');
        $sheet->mergeCells('B5:F5');
        $sheet->getStyle('A3:A5')->getFont()->getColor()->setARGB('FF6B7280');
        $sheet->getStyle('B3:B5')->getFont()->setBold(true);

        $headerRow = 7;
        $sheet->fromArray([['№', 'Номенклатура', 'Опис', 'Кількість', 'Вартість за одн.', 'Сума']], null, 'A'.$headerRow);
        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE5E7EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF9CA3AF'],
                ],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        $itemStartRow = $headerRow + 1;
        foreach ($items as $index => $item) {
            $row = $itemStartRow + $index;
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitCost = (int) ($item['unit_cost'] ?? 0);
            $sum = isset($item['sum']) ? (int) $item['sum'] : $quantity * $unitCost;

            $sheet->fromArray([[
                $index + 1,
                $item['nomenclature'] ?? '—',
                $item['description'] ?? '',
                $quantity,
                $unitCost,
                $sum,
            ]], null, 'A'.$row);
        }

        $itemEndRow = max($itemStartRow, $itemStartRow + count($items) - 1);
        if ($items === []) {
            $sheet->fromArray([['', 'Позиції замовлення відсутні', '', '', '', '']], null, 'A'.$itemStartRow);
        }
        $sheet->getStyle("A{$itemStartRow}:F{$itemEndRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD1D5DB'],
                ],
            ],
        ]);
        $sheet->getStyle("A{$itemStartRow}:A{$itemEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$itemStartRow}:C{$itemEndRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("D{$itemStartRow}:D{$itemEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$itemStartRow}:F{$itemEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$itemStartRow}:F{$itemEndRow}")->getNumberFormat()->setFormatCode('#,##0 "грн"');

        $totalsStartRow = $itemEndRow + 2;
        $sheet->fromArray([
            ['Сума з ПДВ', $totalCost],
            ['Загальна сума сплат', $paymentsTotal],
            ['Сума до сплати', $amountDue],
        ], null, 'E'.$totalsStartRow);
        $totalsEndRow = $totalsStartRow + 2;
        $sheet->getStyle("E{$totalsStartRow}:F{$totalsEndRow}")->getFont()->setBold(true);
        $sheet->getStyle("F{$totalsStartRow}:F{$totalsEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$totalsStartRow}:F{$totalsEndRow}")->getNumberFormat()->setFormatCode('#,##0 "грн"');
        $sheet->getStyle("E{$totalsEndRow}:F{$totalsEndRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->freezePane('A8');
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setRight(0.4)->setBottom(0.4)->setLeft(0.4);
        $sheet->setShowGridlines(false);

        $writer = new Xlsx($spreadsheet);
        $response = response()->streamDownload(function () use ($writer, $spreadsheet): void {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        $response->headers->set(
            'Content-Disposition',
            sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                $filename,
                rawurlencode($filename)
            )
        );

        return $response;
    }

    public function edit(Request $request, Order $order, PermissionService $permissions)
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        abort_unless($permissions->can($request->user(), 'orders_update'), 403);

        $order->ensureItemIds();

        $clients = Client::query()
            ->where(function ($query) use ($order): void {
                $query->where('status', 'active');

                if ($order->client_id) {
                    $query->orWhere('id', $order->client_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('orders.create', [
            'clients' => $clients,
            'order' => $order,
        ]);
    }

    public function update(Request $request, Order $order, PermissionService $permissions): JsonResponse
    {
        $this->authorizeOrderAccess($request, $permissions, $order);
        abort_unless($permissions->can($request->user(), 'orders_update'), 403);

        $order->ensureItemIds();

        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'create_client' => ['nullable', 'boolean'],
            'status' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_keys(Order::STATUSES))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['nullable', 'string', 'max:100'],
            'items.*.nomenclature' => ['required', 'string', 'max:500'],
            'items.*.description' => ['nullable', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'integer', 'min:1'],
        ]);

        $client = $order->client;
        $customerName = $client?->name ?? $order->customer_name;

        $normalizedItems = collect($data['items'])
            ->map(function (array $item): array {
                $quantity = (int) $item['quantity'];
                $unitCost = (int) $item['unit_cost'];
                $itemId = trim((string) ($item['item_id'] ?? ''));

                return [
                    'item_id' => $itemId !== '' ? $itemId : (string) Str::uuid(),
                    'nomenclature' => trim((string) $item['nomenclature']),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'sum' => $quantity * $unitCost,
                ];
            })
            ->values()
            ->all();

        $totalCost = (float) collect($normalizedItems)->sum('sum');
        $oldStatus = (string) ($order->status ?: Order::STATUS_NEW);
        $newStatus = (string) ($data['status'] ?? $oldStatus);

        DB::transaction(function () use ($request, $order, $client, $customerName, $normalizedItems, $totalCost, $oldStatus, $newStatus): void {
            $paymentsTotal = (float) $order->payments()->sum('amount_uah');
            $order->update([
                'status' => $newStatus,
                'customer_name' => $customerName,
                'client_id' => $order->client_id,
                'last_edited_by' => $request->user()?->id,
                'items' => $normalizedItems,
                'payments_total' => $paymentsTotal,
                'amount_due' => $totalCost - $paymentsTotal,
                'total_cost' => $totalCost,
            ]);

            if ($newStatus !== $oldStatus) {
                $this->recordOrderStatusChange($order, $oldStatus, $newStatus, $request->user()?->id);
            }

            $client?->update([
                'last_order_at' => now(),
                'updated_by' => $request->user()?->id,
            ]);
        });

        return response()->json([
            'ok' => true,
            'order_id' => $order->public_id,
            'order_number' => $order->order_number,
            'redirect_url' => route('orders.show', $order),
        ]);
    }

    private function recordOrderStatusChange(Order $order, string $oldStatus, string $newStatus, ?int $userId): void
    {
        $order->histories()->create([
            'user_id' => $userId,
            'operation_type' => 'order_updated',
            'field_name' => 'status',
            'description' => 'Статус замовлення',
            'before_value' => ['value' => Order::STATUSES[$oldStatus] ?? $oldStatus],
            'after_value' => ['value' => Order::STATUSES[$newStatus] ?? $newStatus],
        ]);
    }

    private function authorizeOrderAccess(Request $request, PermissionService $permissions, Order $order): void
    {
        abort_unless($permissions->can($request->user(), 'orders_access'), 403);

        if ($permissions->orderScope($request->user()) === 'own') {
            abort_unless((int) $order->created_by === (int) $request->user()?->id, 403);
        }
    }

    private function findClientByName(string $name, bool $lockForUpdate = false): ?Client
    {
        if ($name === '') {
            return null;
        }

        $query = Client::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function calculation(Request $request, PermissionService $permissions)
    {
        $proposalPublicId = trim((string) $request->query('proposal', ''));
        $proposal = null;
        if ($proposalPublicId !== '') {
            $proposal = OrderProposal::query()
                ->with('editLock')
                ->whereNull('deleted_date')
                ->where('public_id', $proposalPublicId)
                ->first();

            if (! $proposal) {
                abort(404);
            }

            if (! $permissions->can($request->user(), 'orders_edit')) {
                abort(403);
            }

            if ($permissions->ordersListScope($request->user()) === 'own' && (int) $proposal->user_id !== (int) $request->user()?->id) {
                abort(403);
            }

            if ((bool) $proposal->is_autosaved) {
                abort(403);
            }

            $editToken = trim((string) $request->query('edit_token', ''));
            $lock = $proposal->editLock;
            if ($lock && ! $lock->isActive()) {
                $lock->delete();
                $lock = null;
            }

            $sessionEditToken = (string) $request->session()->get("order_proposal_edit_tokens.{$proposal->id}", '');
            if (! $lock && $editToken !== '' && hash_equals($sessionEditToken, $editToken)) {
                $lock = OrderProposalEditLock::create([
                    'order_proposal_id' => $proposal->id,
                    'user_id' => $request->user()->id,
                    'lock_token' => $editToken,
                    'started_at' => now(),
                    'heartbeat_at' => now(),
                ]);
            }

            if (! $lock || $editToken === '' || (string) $lock->lock_token !== $editToken || (int) $lock->user_id !== (int) $request->user()?->id) {
                return redirect()
                    ->route('orders.proposals.show', $proposal)
                    ->with('status', 'Заявка заблокована для редагування. Відкрийте редагування зі сторінки заявки.');
            }

            $lock->update(['heartbeat_at' => now()]);
        }

        $clients = Client::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $productTypes = ProductType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $materialItems = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->where('model_type', 'Матеріал')
            ->get(['internal_code', 'name', 'category', 'material_type', 'thickness_mm', 'service_price', 'purchase_price']);
        $rollingServiceItem = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->where('internal_code', 'SERV-003')
            ->first(['internal_code', 'name', 'service_price', 'purchase_price']);

        $materials = $materialItems
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (! in_array('Матеріал замовника листовий', $materials, true)) {
            $materials[] = 'Матеріал замовника листовий';
        }
        if (! in_array('Матеріал замовника рулонний', $materials, true)) {
            $materials[] = 'Матеріал замовника рулонний';
        }

        sort($materials, SORT_NATURAL | SORT_FLAG_CASE);

        $thicknessByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                return $items
                    ->pluck('thickness_mm')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(fn ($value) => number_format((float) $value, 2, '.', ''))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            })
            ->filter(fn ($values, $key) => $key !== '' && ! empty($values))
            ->toArray();

        $materialTypeByCategory = ProductCategory::query()
            ->whereNotNull('material_type')
            ->pluck('material_type', 'name')
            ->toArray();

        $materialTypeByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) use ($materialTypeByCategory) {
                $types = $items
                    ->map(function (PriceItem $item) use ($materialTypeByCategory) {
                        $directType = trim((string) ($item->material_type ?? ''));
                        if ($directType !== '') {
                            return $directType;
                        }
                        $category = trim((string) ($item->category ?? ''));

                        return $category !== '' ? ($materialTypeByCategory[$category] ?? null) : null;
                    })
                    ->filter(fn ($type) => $type !== null && $type !== '')
                    ->unique()
                    ->values()
                    ->all();

                if (in_array('Рулонний', $types, true)) {
                    return 'Рулонний';
                }

                return $types[0] ?? null;
            })
            ->filter(fn ($type, $material) => $material !== '' && $type !== null)
            ->toArray();

        $materialCategoryByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                $categories = $items
                    ->pluck('category')
                    ->filter(fn ($category) => $category !== null && trim((string) $category) !== '')
                    ->map(fn ($category) => trim((string) $category))
                    ->unique()
                    ->values()
                    ->all();

                if (in_array('Банер', $categories, true)) {
                    return 'Банер';
                }
                if (in_array('Банерна сітка', $categories, true)) {
                    return 'Банерна сітка';
                }

                return $categories[0] ?? null;
            })
            ->filter(fn ($category, $material) => $material !== '' && $category !== null)
            ->toArray();

        $materialCategoriesByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                return $items
                    ->pluck('category')
                    ->filter(fn ($category) => $category !== null && trim((string) $category) !== '')
                    ->map(fn ($category) => trim((string) $category))
                    ->unique()
                    ->values()
                    ->all();
            })
            ->filter(fn ($categories, $material) => $material !== '' && ! empty($categories))
            ->toArray();

        $materialPriceByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                $price = $items
                    ->pluck('service_price')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(fn ($value) => round((float) $value, 2))
                    ->first();

                return $price ?? 0.0;
            })
            ->filter(fn ($price, $material) => $material !== '')
            ->toArray();

        $materialPurchasePriceByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                $price = $items
                    ->pluck('purchase_price')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(fn ($value) => round((float) $value, 2))
                    ->first();

                return $price ?? 0.0;
            })
            ->filter(fn ($price, $material) => $material !== '')
            ->toArray();

        $materialCodeByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                return $items
                    ->pluck('internal_code')
                    ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                    ->map(fn ($value) => trim((string) $value))
                    ->first();
            })
            ->filter(fn ($code, $material) => $material !== '' && $code !== null)
            ->toArray();

        if ($rollingServiceItem) {
            $rollingServiceName = trim((string) $rollingServiceItem->name);
            if ($rollingServiceName !== '') {
                if (! in_array($rollingServiceName, $materials, true)) {
                    $materials[] = $rollingServiceName;
                }
                $materialCodeByMaterial[$rollingServiceName] = 'SERV-003';
                $materialPriceByMaterial[$rollingServiceName] = round((float) ($rollingServiceItem->service_price ?? 0), 2);
                $materialPurchasePriceByMaterial[$rollingServiceName] = round((float) ($rollingServiceItem->purchase_price ?? 0), 2);
            }
        }

        $serviceItems = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->whereIn('internal_code', [
                'SERV-001',
                'SERV-001-MZ',
                'SERV-002',
                'SERV-003',
                'SERV-003-MZ',
                'SERV-004',
                'SERV-005',
                'SERV-005-MZ',
                'SERV-006',
                'SERV-006-MZ',
                'SERV-007',
                'SERV-007-MZ',
                'SERV-008',
                'SERV-008-MZ',
                'SERV-009',
                'SERV-010',
                'SERV-011',
                'SERV-012',
                'SERV-018',
                'SERV-019',
                'SERV-014',
            ])
            ->get(['internal_code', 'service_price', 'purchase_price']);

        $servicePriceByCode = $serviceItems
            ->pluck('service_price', 'internal_code')
            ->map(fn ($value) => round((float) ($value ?? 0), 2))
            ->toArray();

        $servicePurchasePriceByCode = $serviceItems
            ->pluck('purchase_price', 'internal_code')
            ->map(fn ($value) => round((float) ($value ?? 0), 2))
            ->toArray();

        $specialFlmCodes = DB::table('special_flm_set_items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('internal_code')
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '')
            ->values()
            ->all();

        $typeCategoryMatrix = ProductTypeCategoryRule::query()
            ->with(['productType:id', 'productCategory:id,name'])
            ->get()
            ->groupBy('product_type_id')
            ->map(function ($items) {
                return $items
                    ->filter(fn (ProductTypeCategoryRule $rule) => $rule->is_enabled && $rule->productCategory?->name)
                    ->mapWithKeys(fn (ProductTypeCategoryRule $rule) => [trim((string) $rule->productCategory->name) => true])
                    ->toArray();
            })
            ->toArray();

        return view('orders.calculation', [
            'clients' => $clients,
            'productTypes' => $productTypes,
            'materials' => $materials,
            'thicknessByMaterial' => $thicknessByMaterial,
            'materialTypeByMaterial' => $materialTypeByMaterial,
            'materialCategoryByMaterial' => $materialCategoryByMaterial,
            'materialCategoriesByMaterial' => $materialCategoriesByMaterial,
            'materialPriceByMaterial' => $materialPriceByMaterial,
            'materialPurchasePriceByMaterial' => $materialPurchasePriceByMaterial,
            'materialCodeByMaterial' => $materialCodeByMaterial,
            'servicePriceByCode' => $servicePriceByCode,
            'servicePurchasePriceByCode' => $servicePurchasePriceByCode,
            'specialFlmCodes' => $specialFlmCodes,
            'typeCategoryMatrix' => $typeCategoryMatrix,
            'proposalId' => $proposal?->id,
            'initialState' => $proposal?->payload,
            'editLockToken' => isset($editToken) ? $editToken : null,
            'editLockHeartbeatUrl' => $proposal ? route('orders.proposals.edit-lock.heartbeat', $proposal) : null,
            'editLockReleaseUrl' => $proposal ? route('orders.proposals.edit-lock.release', $proposal) : null,
            'canSaveProposal' => $permissions->can($request->user(), 'orders_calc_save'),
            'showPurchaseFields' => $permissions->can($request->user(), 'orders_calc_purchase_visible'),
        ]);
    }

    public function saved(Request $request)
    {
        return view('orders.saved');
    }
}
