<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\OrderProposal;
use App\Models\OrderProposalEditLock;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposalController extends Controller
{
    public function index(Request $request, PermissionService $permissions)
    {
        $sort = (string) $request->query('sort', 'date');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPageRaw = strtolower((string) $request->query('per_page', '20'));
        $allowedPerPage = ['20', '50', '100', 'all'];
        $perPageRaw = in_array($perPageRaw, $allowedPerPage, true) ? $perPageRaw : '20';

        $sortMap = [
            'number' => 'proposal_number',
            'user' => 'users.name',
            'client' => 'client_name',
            'cost' => 'total_cost',
        ];

        $query = OrderProposal::query()
            ->leftJoin('users', 'users.id', '=', 'order_proposals.user_id')
            ->select('order_proposals.*')
            ->with(['user:id,name', 'activeEditLock.user:id,name'])
            ->whereNull('order_proposals.deleted_date');

        if ($permissions->ordersListScope($request->user()) === 'own') {
            $query->where('order_proposals.user_id', $request->user()?->id);
        }

        if ($sort === 'date') {
            $query->orderByRaw("CASE WHEN order_proposals.corrections_count > 0 THEN order_proposals.updated_at ELSE order_proposals.created_at END {$direction}");
        } elseif (isset($sortMap[$sort])) {
            $query->orderBy($sortMap[$sort], $direction);
        } else {
            $query->orderByRaw('CASE WHEN order_proposals.corrections_count > 0 THEN order_proposals.updated_at ELSE order_proposals.created_at END DESC');
        }

        $perPage = match ($perPageRaw) {
            '50' => 50,
            '100' => 100,
            'all' => max(1, (clone $query)->count()),
            default => 20,
        };

        $proposals = $query->paginate($perPage)->withQueryString();

        return view('orders.proposals.index', [
            'proposals' => $proposals,
            'sort' => $sort,
            'direction' => $direction,
            'perPageRaw' => $perPageRaw,
            'canManageProposals' => $permissions->can($request->user(), 'orders_list_edit'),
        ]);
    }

    public function store(Request $request, PermissionService $permissions): JsonResponse|RedirectResponse
    {
        if (!$permissions->can($request->user(), 'orders_calc_save')) {
            abort(403);
        }

        $data = $request->validate([
            'proposal_id' => ['nullable', 'integer', 'exists:order_proposals,id'],
            'edit_lock_token' => ['nullable', 'string', 'max:80'],
            'urgency_coefficient' => ['nullable'],
            'state' => ['required', 'array'],
            'state.products' => ['required', 'array', 'min:1'],
            'state.client_id' => ['nullable', 'integer'],
            'state.client_name' => ['nullable', 'string', 'max:255'],
            'state.urgency_coefficient' => ['nullable'],
            'state.urgencyCoefficient' => ['nullable'],
            'state.summary' => ['nullable', 'array'],
            'state.summary.order_total' => ['nullable'],
        ]);

        // Keep the full calculator state in payload; validated() may drop nested keys not explicitly listed.
        $state = (array) $request->input('state', []);
        $totalCost = (float) Arr::get($state, 'summary.order_total', 0);
        $clientName = trim((string) Arr::get($state, 'client_name', ''));
        $clientId = Arr::get($state, 'client_id');
        $clientId = is_numeric($clientId) ? (int) $clientId : null;
        $resolvedClient = $this->resolveClientForProposal($clientId, $clientName, $request);

        if ($resolvedClient) {
            $state['client_id'] = (int) $resolvedClient->id;
            $state['client_name'] = (string) $resolvedClient->name;
            $clientName = (string) $resolvedClient->name;
        } else {
            $state['client_id'] = null;
            $state['client_name'] = '';
            $clientName = '';
        }

        $proposal = null;
        if (!empty($data['proposal_id'])) {
            $proposal = OrderProposal::query()
                ->whereNull('deleted_date')
                ->find($data['proposal_id']);

            if ($proposal && $permissions->ordersListScope($request->user()) === 'own' && (int) $proposal->user_id !== (int) $request->user()?->id) {
                abort(403);
            }

            $canFinalizeOwnAutosave = $proposal
                && (bool) $proposal->is_autosaved
                && (int) $proposal->user_id === (int) $request->user()?->id
                && (int) $proposal->autosaved_by === (int) $request->user()?->id;

            if ($proposal && !$canFinalizeOwnAutosave && !$permissions->can($request->user(), 'orders_edit')) {
                abort(403);
            }
        }

        if (!$proposal) {
            $proposal = new OrderProposal();
            $proposal->proposal_number = 'TMP-'.uniqid();
            $proposal->user_id = $request->user()?->id;
            $proposal->corrections_count = 0;
        } elseif (!(bool) $proposal->is_autosaved) {
            $proposal->corrections_count = ((int) $proposal->corrections_count) + 1;
        }

        $existingPayload = is_array($proposal->payload ?? null) ? $proposal->payload : [];
        $urgencyCoefficient = Arr::get($state, 'urgency_coefficient')
            ?? Arr::get($state, 'urgencyCoefficient')
            ?? ($data['urgency_coefficient'] ?? null)
            ?? Arr::get($existingPayload, 'urgency_coefficient')
            ?? Arr::get($existingPayload, 'urgencyCoefficient')
            ?? '1.00';
        $urgencyCoefficient = trim((string) $urgencyCoefficient);
        if ($urgencyCoefficient === '') {
            $urgencyCoefficient = '1.00';
        }
        $state['urgency_coefficient'] = $urgencyCoefficient;
        $state['urgencyCoefficient'] = $urgencyCoefficient;

        $summary = Arr::get($state, 'summary', []);
        if (!is_array($summary)) {
            $summary = [];
        }
        $summary['urgency_coefficient'] = $urgencyCoefficient;
        $state['summary'] = $summary;

        $proposal->client_name = $clientName !== '' ? $clientName : null;
        $proposal->total_cost = round($totalCost, 2);
        $proposal->payload = $state;
        if ((bool) $proposal->is_autosaved) {
            $proposal->is_autosaved = false;
            $proposal->autosave_confirmed_by = $request->user()?->id;
            $proposal->autosave_confirmed_at = now();
            $proposal->autosave_token = null;
        }
        $proposal->save();

        if (str_starts_with($proposal->proposal_number, 'TMP-')) {
            $proposal->proposal_number = sprintf('P-%06d', $proposal->id);
            $proposal->save();
        }

        $this->releaseMatchingEditLock($proposal, (string) ($data['edit_lock_token'] ?? ''), (int) $request->user()?->id);

        $redirectUrl = $permissions->can($request->user(), 'orders_proposals')
            ? route('orders.proposals')
            : route('orders.index');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'proposal_id' => $proposal->id,
                'proposal_number' => $proposal->proposal_number,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', 'Заявку збережено.');
    }

    public function show(Request $request, OrderProposal $orderProposal, PermissionService $permissions)
    {
        if ($orderProposal->deleted_date !== null) {
            abort(404);
        }

        if ($permissions->ordersListScope($request->user()) === 'own' && (int) $orderProposal->user_id !== (int) $request->user()?->id) {
            abort(403);
        }

        $orderProposal->load(['autosavedBy:id,name', 'activeEditLock.user:id,name']);

        $state = is_array($orderProposal->payload) ? $orderProposal->payload : [];
        $stateClientId = Arr::get($state, 'client_id');
        $stateClientId = is_numeric($stateClientId) ? (int) $stateClientId : null;
        $linkedClient = $stateClientId ? Client::query()->find($stateClientId) : null;
        $clientDisplayName = trim((string) ($linkedClient?->name ?? ''));
        if ($clientDisplayName === '') {
            $clientDisplayName = trim((string) (Arr::get($state, 'client_name', '') ?: ($orderProposal->client_name ?? '')));
        }

        $products = Arr::get($state, 'products', []);
        $products = is_array($products) ? array_values($products) : [];

        $totalProducts = count($products);
        foreach ($products as $i => &$product) {
            // Must match /orders/calculation label logic: displayProductNumber(productIndex) = length - productIndex
            $product['display_index'] = $totalProducts - $i;
        }
        unset($product);

        return view('orders.proposals.show', [
            'proposal' => $orderProposal,
            'state' => $state,
            'clientDisplayName' => $clientDisplayName,
            'products' => $products,
            'summary' => Arr::get($state, 'summary', []),
            'canEditProposal' => $permissions->can($request->user(), 'orders_edit'),
            'canViewProposalPurchaseCost' => $permissions->can($request->user(), 'orders_list_purchase_visible'),
            'canConfirmAutosave' => (bool) $orderProposal->is_autosaved
                && (int) $orderProposal->autosaved_by === (int) $request->user()?->id,
        ]);
    }

    public function autosave(Request $request, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->can($request->user(), 'orders_calc_save')) {
            abort(403);
        }

        $data = $request->validate([
            'proposal_id' => ['nullable', 'integer', 'exists:order_proposals,id'],
            'autosave_token' => ['required', 'string', 'max:80'],
            'urgency_coefficient' => ['nullable'],
            'state' => ['required', 'array'],
            'state.products' => ['required', 'array', 'min:1'],
            'state.client_id' => ['nullable', 'integer'],
            'state.client_name' => ['nullable', 'string', 'max:255'],
            'state.urgency_coefficient' => ['nullable'],
            'state.urgencyCoefficient' => ['nullable'],
            'state.summary' => ['nullable', 'array'],
            'state.summary.order_total' => ['nullable'],
        ]);

        $state = (array) $request->input('state', []);
        $totalCost = (float) Arr::get($state, 'summary.order_total', 0);
        $clientName = trim((string) Arr::get($state, 'client_name', ''));
        $clientId = Arr::get($state, 'client_id');
        $clientId = is_numeric($clientId) ? (int) $clientId : null;
        $resolvedClient = $this->resolveClientForProposal($clientId, $clientName, $request);

        if ($resolvedClient) {
            $state['client_id'] = (int) $resolvedClient->id;
            $state['client_name'] = (string) $resolvedClient->name;
            $clientName = (string) $resolvedClient->name;
        } else {
            $state['client_id'] = null;
            $state['client_name'] = '';
            $clientName = '';
        }

        $proposal = null;
        if (!empty($data['proposal_id'])) {
            $proposal = OrderProposal::query()
                ->whereNull('deleted_date')
                ->find($data['proposal_id']);

            if (!$proposal || !(bool) $proposal->is_autosaved) {
                abort(404);
            }

            if ((int) $proposal->autosaved_by !== (int) $request->user()?->id
                || (string) $proposal->autosave_token !== (string) $data['autosave_token']) {
                abort(403);
            }
        }

        if (!$proposal) {
            $proposal = new OrderProposal();
            $proposal->proposal_number = 'TMP-'.uniqid();
            $proposal->user_id = $request->user()?->id;
            $proposal->corrections_count = 0;
            $proposal->autosaved_by = $request->user()?->id;
            $proposal->autosave_token = (string) $data['autosave_token'];
        }

        $existingPayload = is_array($proposal->payload ?? null) ? $proposal->payload : [];
        $urgencyCoefficient = Arr::get($state, 'urgency_coefficient')
            ?? Arr::get($state, 'urgencyCoefficient')
            ?? ($data['urgency_coefficient'] ?? null)
            ?? Arr::get($existingPayload, 'urgency_coefficient')
            ?? Arr::get($existingPayload, 'urgencyCoefficient')
            ?? '1.00';
        $urgencyCoefficient = trim((string) $urgencyCoefficient);
        if ($urgencyCoefficient === '') {
            $urgencyCoefficient = '1.00';
        }
        $state['urgency_coefficient'] = $urgencyCoefficient;
        $state['urgencyCoefficient'] = $urgencyCoefficient;

        $summary = Arr::get($state, 'summary', []);
        if (!is_array($summary)) {
            $summary = [];
        }
        $summary['urgency_coefficient'] = $urgencyCoefficient;
        $state['summary'] = $summary;

        $proposal->client_name = $clientName !== '' ? $clientName : null;
        $proposal->total_cost = round($totalCost, 2);
        $proposal->payload = $state;
        $proposal->is_autosaved = true;
        $proposal->autosaved_by = $request->user()?->id;
        $proposal->autosaved_at = now();
        $proposal->autosave_token = (string) $data['autosave_token'];
        $proposal->save();

        if (str_starts_with($proposal->proposal_number, 'TMP-')) {
            $proposal->proposal_number = sprintf('P-%06d', $proposal->id);
            $proposal->save();
        }

        return response()->json([
            'ok' => true,
            'proposal_id' => $proposal->id,
            'proposal_number' => $proposal->proposal_number,
            'autosaved_at' => optional($proposal->autosaved_at)->timezone('Europe/Kiev')->format('d.m.Y H:i'),
        ]);
    }

    public function confirmAutosave(Request $request, OrderProposal $orderProposal): RedirectResponse
    {
        if ($orderProposal->deleted_date !== null) {
            abort(404);
        }

        if (!(bool) $orderProposal->is_autosaved || (int) $orderProposal->autosaved_by !== (int) $request->user()?->id) {
            abort(403);
        }

        $orderProposal->update([
            'is_autosaved' => false,
            'autosave_token' => null,
            'autosave_confirmed_by' => $request->user()?->id,
            'autosave_confirmed_at' => now(),
        ]);

        return redirect()
            ->route('orders.proposals.show', $orderProposal)
            ->with('status', 'Заявку збережено вручну. Автоматичне збереження деактивоване.');
    }

    public function deleteAutosave(Request $request, OrderProposal $orderProposal): RedirectResponse
    {
        if ($orderProposal->deleted_date !== null) {
            abort(404);
        }

        if (!(bool) $orderProposal->is_autosaved || (int) $orderProposal->autosaved_by !== (int) $request->user()?->id) {
            abort(403);
        }

        DB::transaction(function () use ($request, $orderProposal): void {
            $orderProposal->editLock()->delete();
            $orderProposal->update([
                'deleted_by' => $request->user()?->id,
                'deleted_date' => now(),
            ]);
        });

        $request->session()->forget("order_proposal_edit_tokens.{$orderProposal->id}");

        return redirect()
            ->route('orders.proposals')
            ->with('status', 'Автоматично збережений прорахунок видалено.');
    }

    public function startEditLock(Request $request, OrderProposal $orderProposal, PermissionService $permissions): JsonResponse
    {
        $this->authorizeProposalEdit($request, $orderProposal, $permissions);

        if ((bool) $orderProposal->is_autosaved) {
            abort(403);
        }

        $this->forgetStaleEditLock($orderProposal);
        $activeLock = $orderProposal->editLock()->with('user:id,name')->first();
        if ($activeLock && $activeLock->isActive()) {
            return response()->json([
                'ok' => false,
                'message' => 'Заявка вже знаходиться на редагуванні користувачем: '.($activeLock->user?->name ?? '—'),
            ], 409);
        }

        $token = Str::random(48);
        $orderProposal->editLock()->updateOrCreate(
            ['order_proposal_id' => $orderProposal->id],
            [
                'user_id' => $request->user()?->id,
                'lock_token' => $token,
                'started_at' => now(),
                'heartbeat_at' => now(),
            ]
        );

        $request->session()->put("order_proposal_edit_tokens.{$orderProposal->id}", $token);

        return response()->json([
            'ok' => true,
            'edit_url' => route('orders.calculation', [
                'proposal' => $orderProposal->public_id,
                'edit_token' => $token,
            ]),
        ]);
    }

    public function heartbeatEditLock(Request $request, OrderProposal $orderProposal, PermissionService $permissions): JsonResponse
    {
        $this->authorizeProposalEdit($request, $orderProposal, $permissions);

        $data = $request->validate([
            'lock_token' => ['required', 'string', 'max:80'],
        ]);

        $lock = $orderProposal->editLock()
            ->where('lock_token', $data['lock_token'])
            ->where('user_id', $request->user()?->id)
            ->first();

        if (!$lock || !$lock->isActive()) {
            abort(409);
        }

        $lock->update(['heartbeat_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function releaseEditLock(Request $request, OrderProposal $orderProposal, PermissionService $permissions): JsonResponse
    {
        $this->authorizeProposalEdit($request, $orderProposal, $permissions);

        $token = (string) $request->input('lock_token', '');
        $this->releaseMatchingEditLock($orderProposal, $token, (int) $request->user()?->id);

        return response()->json(['ok' => true]);
    }

    public function deactivate(Request $request, PermissionService $permissions): RedirectResponse
    {
        if (!$permissions->can($request->user(), 'orders_list_edit')) {
            abort(403);
        }

        $validated = $request->validate([
            'proposal_ids' => ['required', 'array', 'min:1'],
            'proposal_ids.*' => ['integer', 'distinct', 'exists:order_proposals,id'],
        ]);

        $ids = collect($validated['proposal_ids'])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('orders.proposals')
                ->withErrors(['proposal_ids' => 'Не обрано заявки для видалення.']);
        }

        DB::transaction(function () use ($ids, $request, $permissions): void {
            $query = OrderProposal::query()
                ->whereIn('id', $ids->all())
                ->whereNull('deleted_date');

            if ($permissions->ordersListScope($request->user()) === 'own') {
                $query->where('user_id', $request->user()?->id);
            }

            $query->update([
                    'deleted_by' => $request->user()?->id,
                    'deleted_date' => now(),
                ]);
        });

        return redirect()
            ->route('orders.proposals')
            ->with('status', 'Обрані заявки деактивовано.');
    }

    private function authorizeProposalEdit(Request $request, OrderProposal $proposal, PermissionService $permissions): void
    {
        if ($proposal->deleted_date !== null) {
            abort(404);
        }

        if (!$permissions->can($request->user(), 'orders_edit')) {
            abort(403);
        }

        if ($permissions->ordersListScope($request->user()) === 'own' && (int) $proposal->user_id !== (int) $request->user()?->id) {
            abort(403);
        }
    }

    private function forgetStaleEditLock(OrderProposal $proposal): void
    {
        $lock = $proposal->editLock()->first();
        if ($lock && !$lock->isActive()) {
            $lock->delete();
        }
    }

    private function releaseMatchingEditLock(OrderProposal $proposal, string $token, int $userId): void
    {
        if ($token === '' || $userId <= 0) {
            return;
        }

        $proposal->editLock()
            ->where('lock_token', $token)
            ->where('user_id', $userId)
            ->delete();
    }

    private function resolveClientForProposal(?int $clientId, string $clientName, Request $request): ?Client
    {
        if ($clientId) {
            $clientById = Client::query()->find($clientId);
            if ($clientById) {
                return $clientById;
            }
        }

        $normalizedName = trim($clientName);
        if ($normalizedName === '') {
            return null;
        }

        $normalizedNameLower = mb_strtolower($normalizedName, 'UTF-8');
        $existingClient = Client::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedNameLower])
            ->first();

        if ($existingClient) {
            return $existingClient;
        }

        return DB::transaction(function () use ($normalizedName, $request): Client {
            $tempCode = 'FP-TEMP-'.Str::upper(Str::random(8));
            $userId = $request->user()?->id;

            $client = Client::query()->create([
                'code' => $tempCode,
                'name' => $normalizedName,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $client->update([
                'code' => 'FP-'.str_pad((string) $client->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $client;
        });
    }
}
