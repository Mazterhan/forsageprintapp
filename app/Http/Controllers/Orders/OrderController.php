<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
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

        if (! in_array('Матеріал замовника', $materials, true)) {
            $materials[] = 'Матеріал замовника';
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

        return view('orders.calculation', [
            'clients' => $clients,
            'productTypes' => $productTypes,
            'materials' => $materials,
            'thicknessByMaterial' => $thicknessByMaterial,
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
