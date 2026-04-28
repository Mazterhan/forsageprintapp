<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\OrderProposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposalController extends Controller
{
    public function index(Request $request)
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
            ->with('user:id,name')
            ->whereNull('order_proposals.deleted_date');

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
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user()?->role === 'user') {
            abort(403);
        }

        $data = $request->validate([
            'proposal_id' => ['nullable', 'integer', 'exists:order_proposals,id'],
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
        }

        if (!$proposal) {
            $proposal = new OrderProposal();
            $proposal->proposal_number = 'TMP-'.uniqid();
            $proposal->user_id = $request->user()?->id;
            $proposal->corrections_count = 0;
        } else {
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
        $proposal->save();

        if (str_starts_with($proposal->proposal_number, 'TMP-')) {
            $proposal->proposal_number = sprintf('P-%06d', $proposal->id);
            $proposal->save();
        }

        $redirectUrl = route('orders.proposals');

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

    public function show(OrderProposal $orderProposal)
    {
        if ($orderProposal->deleted_date !== null) {
            abort(404);
        }

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
        ]);
    }

    public function deactivate(Request $request): RedirectResponse
    {
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

        DB::transaction(function () use ($ids, $request): void {
            OrderProposal::query()
                ->whereIn('id', $ids->all())
                ->whereNull('deleted_date')
                ->update([
                    'deleted_by' => $request->user()?->id,
                    'deleted_date' => now(),
                ]);
        });

        return redirect()
            ->route('orders.proposals')
            ->with('status', 'Обрані заявки деактивовано.');
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
