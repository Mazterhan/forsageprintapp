<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductTypeCategoryRule;
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

        $categories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name']);

        $rules = [];
        if (!empty($types) && $categories->isNotEmpty()) {
            $rules = ProductTypeCategoryRule::query()
                ->with('productType:id,name')
                ->whereIn('product_category_id', $categories->pluck('id'))
                ->get()
                ->mapWithKeys(function (ProductTypeCategoryRule $rule) {
                    $typeName = $rule->productType?->name;
                    if ($typeName === null || $typeName === '') {
                        return [];
                    }

                    return [((string) $rule->product_category_id).'|'.$typeName => (bool) $rule->is_enabled];
                })
                ->toArray();
        }

        return view('orders.product-types.index', [
            'types' => $types,
            'categories' => $categories,
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'types' => ['nullable', 'array'],
            'types.*' => ['nullable', 'string', 'max:255'],
            'matrix' => ['nullable', 'array'],
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
                ->withInput([
                    'types' => $uniqueTypes->all(),
                    'matrix' => (array) ($data['matrix'] ?? []),
                ])
                ->withErrors(['types' => __('Знайдено дублікати. Кожен тип виробу має бути унікальним.')]);
        }

        $types = $uniqueTypes->all();
        $matrixRaw = (array) ($data['matrix'] ?? []);

        DB::transaction(function () use ($types, $matrixRaw): void {
            ProductType::query()->delete();

            foreach ($types as $index => $name) {
                ProductType::create([
                    'name' => $name,
                    'sort_order' => $index + 1,
                ]);
            }

            ProductTypeCategoryRule::query()->delete();

            if (empty($types)) {
                return;
            }

            $nameToId = ProductType::query()
                ->pluck('id', 'name')
                ->toArray();

            $rows = [];
            foreach ($matrixRaw as $categoryId => $perType) {
                if (!is_array($perType)) {
                    continue;
                }

                foreach ($perType as $typeName => $flag) {
                    $typeName = trim((string) $typeName);
                    if ($typeName === '' || !isset($nameToId[$typeName])) {
                        continue;
                    }

                    if ((string) $flag !== '1') {
                        continue;
                    }

                    $rows[] = [
                        'product_category_id' => (int) $categoryId,
                        'product_type_id' => (int) $nameToId[$typeName],
                        'is_enabled' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($rows)) {
                ProductTypeCategoryRule::query()->insert($rows);
            }
        });

        return redirect()
            ->route('orders.product-types.index')
            ->with('status', __('Типи виробів збережено.'));
    }
}
