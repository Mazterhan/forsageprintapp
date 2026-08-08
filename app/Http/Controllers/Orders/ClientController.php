<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\OrderProposal;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index(Request $request, PermissionService $permissions)
    {
        $user = $request->user();
        $canAccessOrders = $permissions->can($user, 'orders_access');
        $orderScope = $permissions->orderScope($user);
        $sort = (string) $request->query('sort', 'name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortMap = [
            'name' => 'clients.name',
            'orders_count' => 'client_order_stats.orders_count',
            'category' => 'clients.category',
            'vip' => 'clients.is_vip',
            'manager' => 'client_managers.name',
        ];

        $paymentTotals = DB::table('client_payments')
            ->selectRaw('order_id, SUM(amount_uah) as total')
            ->whereNotNull('order_id')
            ->groupBy('order_id');

        $orderStats = DB::table('orders')
            ->leftJoinSub($paymentTotals, 'client_order_payment_totals', function ($join): void {
                $join->on('client_order_payment_totals.order_id', '=', 'orders.id');
            })
            ->whereNotNull('orders.client_id')
            ->groupBy('orders.client_id')
            ->selectRaw('orders.client_id, COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(client_order_payment_totals.total, 0) <= 0 THEN 1 ELSE 0 END) as unpaid_orders_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(client_order_payment_totals.total, 0) > 0 AND COALESCE(client_order_payment_totals.total, 0) < orders.total_cost THEN 1 ELSE 0 END) as partially_paid_orders_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(client_order_payment_totals.total, 0) > 0 AND COALESCE(client_order_payment_totals.total, 0) = orders.total_cost THEN 1 ELSE 0 END) as fully_paid_orders_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(client_order_payment_totals.total, 0) > orders.total_cost THEN 1 ELSE 0 END) as overpaid_orders_count');

        if (! $canAccessOrders) {
            $orderStats->whereRaw('1 = 0');
        } elseif ($orderScope === 'own') {
            $orderStats->where('orders.created_by', $user?->id);
        }

        $query = Client::query()
            ->leftJoin('users as client_managers', 'client_managers.id', '=', 'clients.manager_id')
            ->leftJoinSub($orderStats, 'client_order_stats', function ($join): void {
                $join->on('client_order_stats.client_id', '=', 'clients.id');
            })
            ->select('clients.*')
            ->addSelect([
                DB::raw('COALESCE(client_order_stats.orders_count, 0) as orders_count'),
                DB::raw('COALESCE(client_order_stats.unpaid_orders_count, 0) as unpaid_orders_count'),
                DB::raw('COALESCE(client_order_stats.partially_paid_orders_count, 0) as partially_paid_orders_count'),
                DB::raw('COALESCE(client_order_stats.fully_paid_orders_count, 0) as fully_paid_orders_count'),
                DB::raw('COALESCE(client_order_stats.overpaid_orders_count, 0) as overpaid_orders_count'),
            ])
            ->with('manager');

        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));
        $vip = $request->input('vip');
        $managerId = $request->input('manager_id');

        if ($search !== '') {
            $query->where('clients.name', 'like', '%'.$search.'%');
        }

        if ($category !== '') {
            $query->where('clients.category', $category);
        }

        if ($vip === '0' || $vip === '1') {
            $query->where('clients.is_vip', (int) $vip);
        }

        if ($managerId !== null && $managerId !== '') {
            $query->where('clients.manager_id', (int) $managerId);
        }

        if ($sort === 'status') {
            $query->orderByRaw("CASE clients.status
                WHEN 'active' THEN 1
                WHEN 'paused' THEN 2
                WHEN 'blocked' THEN 3
                ELSE 4
            END {$direction}");
        } elseif (isset($sortMap[$sort])) {
            $query->orderBy($sortMap[$sort], $direction);
        } else {
            $sort = 'name';
            $query->orderBy('clients.name', 'asc');
        }

        if ($sort !== 'name') {
            $query->orderBy('clients.name');
        }

        $clients = $query->paginate(15)->withQueryString();

        $categories = Client::query()
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $managers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('clients.index', [
            'clients' => $clients,
            'categories' => $categories,
            'managers' => $managers,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'vip' => $vip ?? '',
                'manager_id' => $managerId ?? '',
            ],
            'clientPermissions' => [
                'create' => $permissions->can($user, 'orders_clients_create'),
            ],
        ]);
    }

    public function create()
    {
        $managers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('clients.create', [
            'managers' => $managers,
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();
        $data['is_vip'] = $request->boolean('is_vip');
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $client = DB::transaction(function () use ($data) {
            $tempCode = 'FP-TEMP-'.Str::upper(Str::random(8));
            $client = Client::create(array_merge($data, ['code' => $tempCode]));

            $client->update([
                'code' => 'FP-'.str_pad((string) $client->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $client;
        });

        return redirect()->route('orders.clients.edit', $client)->with('status', 'Замовника створено.');
    }

    public function show(Request $request, Client $client, PermissionService $permissions)
    {
        return $this->renderCard($request, $client, true, $permissions);
    }

    public function edit(Request $request, Client $client, PermissionService $permissions)
    {
        return $this->renderCard($request, $client, false, $permissions);
    }

    private function renderCard(Request $request, Client $client, bool $readOnly, PermissionService $permissions)
    {
        $user = $request->user();
        $clientPermissions = [
            'edit' => $permissions->can($user, 'orders_clients_edit'),
            'payments' => $permissions->can($user, 'orders_clients_payments'),
            'orders' => $permissions->can($user, 'orders_access'),
        ];

        $client->load([
            'manager',
            'createdBy',
            'updatedBy',
        ]);

        if ($clientPermissions['payments']) {
            $client->load([
                'payments' => fn ($query) => $query->latest('paid_at'),
                'payments.order:id,public_id,order_number,amount_due',
                'payments.createdBy:id,name',
                'payments.updatedBy:id,name',
                'payments.histories.user:id,name',
                'payments.automaticOverpayment:id,public_id,source_payment_id,amount_uah',
            ]);
        } else {
            $client->setRelation('payments', collect());
        }

        $managers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $paymentModalData = $client->payments->map(function ($payment) use ($client): array {
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
                'paymentType' => $payment->payment_type,
                'fromOverpayment' => $payment->is_from_overpayment,
                'isAutomatic' => $payment->is_automatic,
                'automaticOverpaymentId' => $payment->automaticOverpayment?->public_id,
                'orderPublicId' => $payment->order?->public_id,
                'orderNumber' => $payment->order?->order_number,
                'orderAmountDue' => $payment->order
                    ? max(0, (int) round((float) $payment->order->amount_due + (float) $payment->amount_uah))
                    : null,
                'comment' => $payment->comment ?? '',
                'updateUrl' => route('orders.clients.payments.update', [$client, $payment]),
                'histories' => $payment->histories->map(fn ($history): array => [
                    'date' => $history->created_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i'),
                    'user' => $history->user?->name ?? '—',
                    'changes' => $history->changes,
                ])->values()->all(),
            ];
        })->values();

        $orderSort = (string) $request->query('order_sort', 'date');
        $orderDirection = strtolower((string) $request->query('order_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderSortMap = [
            'number' => 'orders.order_number',
            'customer' => 'orders.customer_name',
            'user' => 'order_editors.name',
            'amount_due' => 'orders.amount_due',
            'total_cost' => 'orders.total_cost',
        ];
        $paymentTotals = DB::table('client_payments')
            ->selectRaw('order_id, SUM(amount_uah) as total')
            ->whereNotNull('order_id')
            ->groupBy('order_id');
        $clientOrdersQuery = $client->orders()
            ->leftJoin('users as order_editors', 'order_editors.id', '=', 'orders.last_edited_by')
            ->leftJoinSub($paymentTotals, 'card_order_payment_totals', function ($join): void {
                $join->on('card_order_payment_totals.order_id', '=', 'orders.id');
            })
            ->select('orders.*')
            ->addSelect(DB::raw('COALESCE(card_order_payment_totals.total, 0) as linked_payments_total'))
            ->with('lastEditedBy:id,name');

        if (! $clientPermissions['orders']) {
            $clientOrdersQuery->whereRaw('1 = 0');
        } elseif ($permissions->orderScope($user) === 'own') {
            $clientOrdersQuery->where('orders.created_by', $user?->id);
        }

        if ($orderSort === 'date') {
            $clientOrdersQuery->orderBy('orders.updated_at', $orderDirection);
        } elseif ($orderSort === 'payment') {
            $clientOrdersQuery->orderByRaw("CASE
                WHEN COALESCE(card_order_payment_totals.total, 0) <= 0 THEN 1
                WHEN COALESCE(card_order_payment_totals.total, 0) < orders.total_cost THEN 2
                WHEN COALESCE(card_order_payment_totals.total, 0) = orders.total_cost THEN 3
                ELSE 4
            END {$orderDirection}");
        } elseif (isset($orderSortMap[$orderSort])) {
            $clientOrdersQuery->orderBy($orderSortMap[$orderSort], $orderDirection);
        } else {
            $orderSort = 'date';
            $clientOrdersQuery->orderBy('orders.updated_at', 'desc');
        }

        $clientOrders = $clientOrdersQuery
            ->paginate(20, ['*'], 'orders_page')
            ->withQueryString()
            ->appends(['section' => 'orders']);

        return view('clients.edit', [
            'client' => $client,
            'managers' => $managers,
            'paymentModalData' => $paymentModalData,
            'requestedSection' => $request->query('section'),
            'readOnly' => $readOnly,
            'clientOrders' => $clientOrders,
            'orderSort' => $orderSort,
            'orderDirection' => $orderDirection,
            'clientPermissions' => $clientPermissions,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->validated();
        $data['is_vip'] = $request->boolean('is_vip');
        $data['updated_by'] = Auth::id();
        $previousName = trim((string) ($client->name ?? ''));

        $client->update($data);
        $this->syncProposalClientName($client, $previousName);

        return redirect()->route('orders.clients.edit', $client)->with('status', 'Дані замовника оновлено.');
    }

    public function deactivate(Client $client)
    {
        $client->update([
            'status' => 'blocked',
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('orders.clients.index')->with('status', 'Замовника деактивовано.');
    }

    private function syncProposalClientName(Client $client, string $previousName): void
    {
        $clientId = (int) $client->id;
        $currentName = trim((string) ($client->name ?? ''));
        $previousNameNormalized = mb_strtolower($previousName, 'UTF-8');

        OrderProposal::query()
            ->select(['id', 'client_name', 'payload'])
            ->chunkById(200, function ($proposals) use ($clientId, $currentName, $previousNameNormalized): void {
                foreach ($proposals as $proposal) {
                    $payload = is_array($proposal->payload ?? null) ? $proposal->payload : [];
                    $payloadClientId = Arr::get($payload, 'client_id');
                    $payloadClientId = is_numeric($payloadClientId) ? (int) $payloadClientId : null;

                    $payloadClientName = trim((string) Arr::get($payload, 'client_name', ''));
                    $proposalClientName = trim((string) ($proposal->client_name ?? ''));
                    $payloadClientNameNormalized = mb_strtolower($payloadClientName, 'UTF-8');
                    $proposalClientNameNormalized = mb_strtolower($proposalClientName, 'UTF-8');

                    $belongsById = $payloadClientId === $clientId;
                    $belongsByName = $previousNameNormalized !== '' && (
                        $payloadClientNameNormalized === $previousNameNormalized
                        || $proposalClientNameNormalized === $previousNameNormalized
                    );

                    if (! $belongsById && ! $belongsByName) {
                        continue;
                    }

                    $payload['client_id'] = $clientId;
                    $payload['client_name'] = $currentName;
                    $proposal->client_name = $currentName;
                    $proposal->payload = $payload;
                    $proposal->save();
                }
            });
    }
}
