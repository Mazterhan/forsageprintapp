<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\PriceItem;
use App\Models\ProductCategory;
use App\Models\ProductTypeCategoryRule;
use App\Models\ProductType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductTypeController extends Controller
{
    public function index(): View
    {
        $hasServiceInternalCodeColumn = Schema::hasColumn('product_types', 'service_internal_code');

        $typesQuery = ProductType::query()
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($hasServiceInternalCodeColumn) {
            $typesQuery->select(['id', 'name', 'service_internal_code']);
        } else {
            $typesQuery->select(['id', 'name']);
        }

        $types = $typesQuery->get();

        $typeNames = $types->pluck('name')->all();

        $serviceInternalCodes = PriceItem::query()
            ->where('model_type', 'Послуга')
            ->where('is_active', true)
            ->where('visible', true)
            ->orderBy('internal_code')
            ->pluck('internal_code')
            ->all();

        $categories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name']);

        $rules = [];
        if (!empty($typeNames) && $categories->isNotEmpty()) {
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
            'typeNames' => $typeNames,
            'serviceInternalCodes' => $serviceInternalCodes,
            'categories' => $categories,
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'types' => ['nullable', 'array'],
            'types.*' => ['nullable', 'string', 'max:255'],
            'service_codes' => ['nullable', 'array'],
            'service_codes.*' => ['nullable', 'string', 'max:64'],
            'matrix' => ['nullable', 'array'],
        ]);

        $rawTypes = collect($data['types'] ?? [])
            ->map(fn ($value) => trim((string) $value));

        $rawServiceCodes = collect($data['service_codes'] ?? []);

        $typeRows = $rawTypes
            ->map(function (string $typeName, int $index) use ($rawServiceCodes): array {
                return [
                    'name' => trim($typeName),
                    'service_internal_code' => trim((string) ($rawServiceCodes->get($index, ''))),
                ];
            })
            ->filter(fn (array $row) => $row['name'] !== '')
            ->values();

        $normalize = static fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $seen = [];
        $hasDuplicates = false;
        $uniqueTypes = $typeRows->filter(function (array $row) use (&$seen, &$hasDuplicates, $normalize): bool {
            $key = $normalize($row['name']);
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
                    'types' => $uniqueTypes->pluck('name')->all(),
                    'service_codes' => $uniqueTypes->pluck('service_internal_code')->all(),
                    'matrix' => (array) ($data['matrix'] ?? []),
                ])
                ->withErrors(['types' => __('Знайдено дублікати. Кожен тип виробу має бути унікальним.')]);
        }

        $types = $uniqueTypes->all();
        $matrixRaw = (array) ($data['matrix'] ?? []);

        DB::transaction(function () use ($types, $matrixRaw): void {
            $hasServiceInternalCodeColumn = Schema::hasColumn('product_types', 'service_internal_code');
            $now = now();

            if (!empty($types)) {
                $typeRowsForUpsert = collect($types)
                    ->values()
                    ->map(function (array $typeRow, int $index) use ($hasServiceInternalCodeColumn, $now): array {
                        $row = [
                            'name' => $typeRow['name'],
                            'sort_order' => $index + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if ($hasServiceInternalCodeColumn) {
                            $row['service_internal_code'] = ($typeRow['service_internal_code'] !== '' ? $typeRow['service_internal_code'] : null);
                        }

                        return $row;
                    })
                    ->all();

                $updateColumns = ['sort_order', 'updated_at'];
                if ($hasServiceInternalCodeColumn) {
                    $updateColumns[] = 'service_internal_code';
                }

                ProductType::query()->upsert(
                    $typeRowsForUpsert,
                    ['name'],
                    $updateColumns
                );
            }

            $nameToId = ProductType::query()
                ->pluck('id', 'name')
                ->toArray();

            $matrixRowsForUpsert = [];
            foreach ($matrixRaw as $categoryId => $perType) {
                if (!is_array($perType)) {
                    continue;
                }

                foreach ($perType as $typeName => $flag) {
                    $typeName = trim((string) $typeName);
                    if ($typeName === '' || !isset($nameToId[$typeName])) {
                        continue;
                    }

                    $matrixRowsForUpsert[] = [
                        'product_category_id' => (int) $categoryId,
                        'product_type_id' => (int) $nameToId[$typeName],
                        'is_enabled' => ((string) $flag === '1'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($matrixRowsForUpsert)) {
                ProductTypeCategoryRule::query()->upsert(
                    $matrixRowsForUpsert,
                    ['product_category_id', 'product_type_id'],
                    ['is_enabled', 'updated_at']
                );
            }
        });

        return redirect()
            ->route('orders.product-types.index')
            ->with('status', __('Типи виробів збережено.'));
    }
}
