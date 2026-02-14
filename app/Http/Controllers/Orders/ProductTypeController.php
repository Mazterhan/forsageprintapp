<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductTypeController extends Controller
{
    public function index(): View
    {
        $types = ProductType::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();

        return view('orders.product-types.index', [
            'types' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'types' => ['nullable', 'array'],
            'types.*' => ['nullable', 'string', 'max:255'],
        ]);

        $types = collect($data['types'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values();

        DB::transaction(function () use ($types): void {
            ProductType::query()->delete();

            foreach ($types as $index => $name) {
                ProductType::create([
                    'name' => $name,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('orders.product-types.index')
            ->with('status', __('Типи виробів збережено.'));
    }
}
