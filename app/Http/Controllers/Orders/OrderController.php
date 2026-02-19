<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ProductCategory;
use App\Models\ProductTypeCategoryRule;
use App\Models\ProductType;
use App\Models\Tariff;
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
            ->get(['id', 'name', 'price_type']);

        $productTypes = ProductType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $materials = Tariff::query()
            ->where('is_active', true)
            ->whereHas('productGroup')
            ->with('productGroup:id,name')
            ->get()
            ->pluck('productGroup.name')
            ->filter()
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

        $thicknessByMaterial = Tariff::query()
            ->where('is_active', true)
            ->whereNotNull('sheet_thickness_mm')
            ->whereHas('productGroup')
            ->with('productGroup:id,name')
            ->get()
            ->groupBy(fn (Tariff $tariff) => (string) optional($tariff->productGroup)->name)
            ->map(function ($items) {
                return $items
                    ->pluck('sheet_thickness_mm')
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

        $materialTypeByMaterial = Tariff::query()
            ->where('is_active', true)
            ->whereHas('productGroup')
            ->with('productGroup:id,name')
            ->get(['product_group_id', 'category'])
            ->groupBy(fn (Tariff $tariff) => (string) optional($tariff->productGroup)->name)
            ->map(function ($items) use ($materialTypeByCategory) {
                $types = $items
                    ->map(fn (Tariff $tariff) => $materialTypeByCategory[$tariff->category] ?? null)
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

        $materialCategoryByMaterial = Tariff::query()
            ->where('is_active', true)
            ->whereHas('productGroup')
            ->with('productGroup:id,name')
            ->get(['product_group_id', 'category'])
            ->groupBy(fn (Tariff $tariff) => (string) optional($tariff->productGroup)->name)
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

        $materialCategoriesByMaterial = Tariff::query()
            ->where('is_active', true)
            ->whereHas('productGroup')
            ->with('productGroup:id,name')
            ->get(['product_group_id', 'category'])
            ->groupBy(fn (Tariff $tariff) => (string) optional($tariff->productGroup)->name)
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
            'typeCategoryMatrix' => $typeCategoryMatrix,
            'priceOptions' => [
                ['value' => 'retail', 'label' => 'Роздрібна ціна'],
                ['value' => 'wholesale', 'label' => 'Оптова ціна'],
                ['value' => 'vip', 'label' => 'VIP ціна'],
            ],
        ]);
    }

    public function saved(Request $request)
    {
        return view('orders.saved');
    }
}
