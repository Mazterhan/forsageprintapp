<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\OrderProposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $sort = (string) $request->query('sort', 'date');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'date' => 'created_at',
            'number' => 'proposal_number',
            'user' => 'users.name',
            'client' => 'client_name',
            'cost' => 'total_cost',
        ];

        $proposals = OrderProposal::query()
            ->leftJoin('users', 'users.id', '=', 'order_proposals.user_id')
            ->select('order_proposals.*')
            ->with('user:id,name')
            ->when(isset($sortMap[$sort]), function ($query) use ($sortMap, $sort, $direction) {
                $query->orderBy($sortMap[$sort], $direction);
            }, function ($query) {
                $query->orderByDesc('order_proposals.created_at');
            })
            ->paginate(30)
            ->withQueryString();

        return view('orders.proposals.index', [
            'proposals' => $proposals,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'proposal_id' => ['nullable', 'integer', 'exists:order_proposals,id'],
            'state' => ['required', 'array'],
            'state.products' => ['required', 'array', 'min:1'],
            'state.client_name' => ['nullable', 'string', 'max:255'],
            'state.summary.order_total' => ['nullable'],
        ]);

        $state = $data['state'];
        $totalCost = (float) Arr::get($state, 'summary.order_total', 0);
        $clientName = trim((string) Arr::get($state, 'client_name', ''));

        $proposal = null;
        if (!empty($data['proposal_id'])) {
            $proposal = OrderProposal::query()->find($data['proposal_id']);
        }

        if (!$proposal) {
            $proposal = new OrderProposal();
            $proposal->proposal_number = 'TMP-'.uniqid();
            $proposal->user_id = $request->user()?->id;
            $proposal->corrections_count = 0;
        } else {
            $proposal->corrections_count = ((int) $proposal->corrections_count) + 1;
        }

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
        $state = is_array($orderProposal->payload) ? $orderProposal->payload : [];
        $products = Arr::get($state, 'products', []);

        return view('orders.proposals.show', [
            'proposal' => $orderProposal,
            'state' => $state,
            'products' => is_array($products) ? $products : [],
            'summary' => Arr::get($state, 'summary', []),
        ]);
    }
}
