<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PriceItem;
use App\Models\ProductCategory;
use App\Models\ProductTypeCategoryRule;
use App\Models\ProductType;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return view('orders.index');
    }

    public function calculation(Request $request)
    {
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
            ->get(['internal_code', 'name', 'category', 'material_type', 'thickness_mm', 'service_price']);
        $rollingServiceItem = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->where('internal_code', 'SERV-003')
            ->first(['internal_code', 'name', 'service_price']);

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
            ->filter(fn ($categories, $material) => $material !== '' && !empty($categories))
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
                if (!in_array($rollingServiceName, $materials, true)) {
                    $materials[] = $rollingServiceName;
                }
                $materialCodeByMaterial[$rollingServiceName] = 'SERV-003';
                $materialPriceByMaterial[$rollingServiceName] = round((float) ($rollingServiceItem->service_price ?? 0), 2);
            }
        }

        $servicePriceByCode = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->whereIn('internal_code', [
                'SERV-001',
                'SERV-001-MZ',
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
                'SERV-014',
            ])
            ->pluck('service_price', 'internal_code')
            ->map(fn ($value) => round((float) ($value ?? 0), 2))
            ->toArray();

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
            'materialCodeByMaterial' => $materialCodeByMaterial,
            'servicePriceByCode' => $servicePriceByCode,
            'typeCategoryMatrix' => $typeCategoryMatrix,
        ]);
    }

    public function saved(Request $request)
    {
        return view('orders.saved');
    }
}
