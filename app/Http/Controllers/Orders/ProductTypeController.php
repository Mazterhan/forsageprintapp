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

        $rawTypes = collect($data['types'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        $normalize = static fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $seen = [];
        $hasDuplicates = false;
        $uniqueTypes = $rawTypes->filter(function (string $value) use (&$seen, &$hasDuplicates, $normalize): bool {
            $key = $normalize($value);
            if (isset($seen[$key])) {
                $hasDuplicates = true;
                return false;
            }
            $seen[$key] = true;
            return true;
        })->values();

        if ($hasDuplicates) {
            return redirect()
                ->route('orders.product-types.index')
                ->withInput(['types' => $uniqueTypes->all()])
                ->withErrors(['types' => __('Знайдено дублікати. Кожен тип виробу має бути унікальним.')]);
        }

        $types = $uniqueTypes;

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
