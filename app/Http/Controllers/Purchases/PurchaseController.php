<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $supplierIds = array_filter((array) $request->query('suppliers', []));
        $itemSearch = (string) $request->query('item_search', '');
        $category = (string) $request->query('category', '');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        $categories = Supplier::query()
            ->when(! empty($supplierIds), function ($query) use ($supplierIds) {
                $query->whereIn('id', $supplierIds);
            })
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $latestItems = PurchaseItem::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('supplier_id', 'internal_code');

        $items = PurchaseItem::query()
            ->joinSub($latestItems, 'latest_items', function ($join) {
                $join->on('purchase_items.id', '=', 'latest_items.id');
            })
            ->with(['supplier', 'purchase'])
            ->when(! empty($supplierIds), function ($query) use ($supplierIds) {
                $query->whereIn('purchase_items.supplier_id', $supplierIds);
            })
            ->when($category !== '', function ($query) use ($category) {
                $query->whereHas('supplier', function ($supplierQuery) use ($category) {
                    $supplierQuery->where('category', $category);
                });
            })
            ->when($itemSearch !== '', function ($query) use ($itemSearch) {
                $query->where(function ($sub) use ($itemSearch) {
                    $sub
                        ->where('purchase_items.internal_code', 'like', "%{$itemSearch}%")
                        ->orWhere('purchase_items.name', 'like', "%{$itemSearch}%")
                        ->orWhere('purchase_items.external_code', 'like', "%{$itemSearch}%");
                });
            })
            ->orderByDesc('purchase_items.imported_at')
            ->paginate(20)
            ->withQueryString();

        return view('purchases.index', [
            'items' => $items,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'filters' => [
                'suppliers' => $supplierIds,
                'item_search' => $itemSearch,
                'category' => $category,
            ],
        ]);
    }
}
