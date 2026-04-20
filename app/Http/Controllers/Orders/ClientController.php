<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\OrderProposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()->with('manager');

        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));
        $vip = $request->input('vip');
        $managerId = $request->input('manager_id');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($vip === '0' || $vip === '1') {
            $query->where('is_vip', (int) $vip);
        }

        if ($managerId !== null && $managerId !== '') {
            $query->where('manager_id', (int) $managerId);
        }

        $clients = $query->orderBy('name')->paginate(15)->withQueryString();

        $categories = Client::query()
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $managers = User::query()
            ->where('role', 'manager')
            ->orderBy('name')
            ->get();

        return view('clients.index', [
            'clients' => $clients,
            'categories' => $categories,
            'managers' => $managers,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'vip' => $vip ?? '',
                'manager_id' => $managerId ?? '',
            ],
        ]);
    }

    public function create()
    {
        $managers = User::query()
            ->where('role', 'manager')
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

    public function edit(Client $client)
    {
        $client->load(['manager', 'createdBy', 'updatedBy']);

        $managers = User::query()
            ->where('role', 'manager')
            ->orderBy('name')
            ->get();

        return view('clients.edit', [
            'client' => $client,
            'managers' => $managers,
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
