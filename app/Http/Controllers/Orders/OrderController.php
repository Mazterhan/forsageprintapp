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
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request, PermissionService $permissions)
    {
        $sort = (string) $request->query('sort', 'date');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPageRaw = strtolower((string) $request->query('per_page', '20'));
        $perPageRaw = in_array($perPageRaw, ['20', '50', '100', 'all'], true) ? $perPageRaw : '20';

        $sortMap = [
            'number' => 'orders.order_number',
            'customer' => 'orders.customer_name',
            'user' => 'users.name',
            'amount_due' => 'orders.amount_due',
            'total_cost' => 'orders.total_cost',
        ];

        $paymentTotals = DB::table('client_payments')
            ->selectRaw('order_id, SUM(amount_uah) as total')
            ->whereNotNull('order_id')
            ->groupBy('order_id');

        $query = Order::query()
            ->leftJoin('users', 'users.id', '=', 'orders.last_edited_by')
            ->leftJoinSub($paymentTotals, 'order_payment_totals', function ($join): void {
                $join->on('order_payment_totals.order_id', '=', 'orders.id');
            })
            ->select('orders.*')
            ->addSelect(DB::raw('COALESCE(order_payment_totals.total, 0) as linked_payments_total'))
            ->with('lastEditedBy:id,name');

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
            'ordersPermissions' => [
                'calculation' => $permissions->can($request->user(), 'orders_calculation'),
                'proposals' => $permissions->can($request->user(), 'orders_proposals'),
                'clients' => $permissions->can($request->user(), 'orders_clients_manage'),
            ],
        ]);
    }

    public function create()
    {
        $clients = Client::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('orders.create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'create_client' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nomenclature' => ['required', 'string', 'max:500'],
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
                'customer_name' => $client->name,
                'client_id' => $client->id,
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

    public function show(Order $order)
    {
        $order->load([
            'client:id,public_id,name',
            'lastEditedBy:id,name',
            'histories.user:id,name',
            'payments' => fn ($query) => $query->latest('paid_at'),
            'payments.createdBy:id,name',
            'payments.histories.user:id,name',
        ]);

        $paymentModalData = $order->payments->map(function ($payment) use ($order): array {
            return [
                'id' => $payment->public_id,
                'amount' => $payment->amount,
                'amountUah' => $payment->amount_uah,
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
                'updateUrl' => route('orders.clients.payments.update', [$order->client, $payment]),
                'histories' => $payment->histories->map(fn ($history): array => [
                    'date' => $history->created_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i'),
                    'user' => $history->user?->name ?? '—',
                    'changes' => $history->changes,
                ])->values()->all(),
            ];
        })->values();
        $clientOverpaymentTotal = $order->client_id
            ? (int) ClientPayment::query()
                ->where('client_id', $order->client_id)
                ->where('payment_type', 'prepayment')
                ->sum('amount_uah')
                - (int) ClientPayment::query()
                    ->where('client_id', $order->client_id)
                    ->where('is_from_overpayment', true)
                    ->sum('amount_uah')
            : 0;
        $orderPaymentsTotal = (float) $order->payments->sum('amount_uah');
        $canAddOrderPayment = $orderPaymentsTotal <= 0 || $orderPaymentsTotal < (float) $order->total_cost;

        return view('orders.show', [
            'order' => $order,
            'paymentModalData' => $paymentModalData,
            'clientOverpaymentTotal' => max(0, $clientOverpaymentTotal),
            'canAddOrderPayment' => $canAddOrderPayment,
        ]);
    }

    public function edit(Order $order)
    {
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

    public function update(Request $request, Order $order): JsonResponse
    {
        $order->ensureItemIds();

        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'create_client' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['nullable', 'string', 'max:100'],
            'items.*.nomenclature' => ['required', 'string', 'max:500'],
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
                $itemId = trim((string) ($item['item_id'] ?? ''));

                return [
                    'item_id' => $itemId !== '' ? $itemId : (string) Str::uuid(),
                    'nomenclature' => trim((string) $item['nomenclature']),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'sum' => $quantity * $unitCost,
                ];
            })
            ->values()
            ->all();

        $totalCost = (float) collect($normalizedItems)->sum('sum');

        DB::transaction(function () use ($request, $order, $client, $customerName, $normalizedItems, $totalCost): void {
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

            $paymentsTotal = (float) $order->payments()->sum('amount_uah');
            $order->update([
                'customer_name' => $client->name,
                'client_id' => $client->id,
                'last_edited_by' => $request->user()?->id,
                'items' => $normalizedItems,
                'payments_total' => $paymentsTotal,
                'amount_due' => $totalCost - $paymentsTotal,
                'total_cost' => $totalCost,
            ]);

            $client->update([
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
