<?php

namespace App\Http\Controllers\Tariffs;

use App\Http\Controllers\Controller;
use App\Models\PricingHistory;
use App\Models\PricingItem;
use App\Models\PurchaseItem;
use App\Models\Subcontractor;
use App\Models\Tariff;
use App\Models\TariffCrossLink;
use App\Models\Supplier;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TariffController extends Controller
{
    public function index(Request $request): View
    {
        $childInternalCodes = TariffCrossLink::query()
            ->pluck('child_internal_code')
            ->all();

        $subcontractors = Subcontractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $subcontractorIds = array_filter((array) $request->query('subcontractors', []));
        $extraPriceFilters = array_filter((array) $request->query('extra_prices', []));
        $category = (string) $request->query('category', '');
        $productGroupId = (string) $request->query('product_group_id', '');
        $search = (string) $request->query('search', '');
        $priceFrom = $request->query('price_from');
        $priceTo = $request->query('price_to');
        $sort = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $categories = Tariff::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $productGroups = ProductGroup::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $extraPriceOptions = [
            'wholesale' => 'оптова ціна',
            'urgent' => 'VIP ціна',
        ];

        $sortMap = [
            'internal_code' => 'tariffs.internal_code',
            'name' => 'tariffs.name',
            'category' => 'tariffs.category',
            'product_group' => 'product_groups.name',
            'sale_price' => 'tariffs.sale_price',
            'wholesale_price' => 'tariffs.wholesale_price',
            'urgent_price' => 'tariffs.urgent_price',
            'subcontractor' => 'subcontractors.name',
        ];

        $tariffs = Tariff::query()
            ->leftJoin('subcontractors', 'subcontractors.id', '=', 'tariffs.subcontractor_id')
            ->leftJoin('product_groups', 'product_groups.id', '=', 'tariffs.product_group_id')
            ->select('tariffs.*')
            ->with(['subcontractor', 'productGroup'])
            ->where('tariffs.is_active', true)
            ->when(! empty($childInternalCodes), function ($query) use ($childInternalCodes) {
                $query->whereNotIn('tariffs.internal_code', $childInternalCodes);
            })
            ->when(! empty($subcontractorIds), function ($query) use ($subcontractorIds) {
                $query->whereIn('tariffs.subcontractor_id', $subcontractorIds);
            })
            ->when($category !== '', function ($query) use ($category) {
                $query->where('tariffs.category', $category);
            })
            ->when($productGroupId !== '', function ($query) use ($productGroupId) {
                $query->where('tariffs.product_group_id', (int) $productGroupId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('tariffs.name', 'like', "%{$search}%");
            })
            ->when($priceFrom !== null && $priceFrom !== '', function ($query) use ($priceFrom) {
                $query->where('tariffs.sale_price', '>=', (float) $priceFrom);
            })
            ->when($priceTo !== null && $priceTo !== '', function ($query) use ($priceTo) {
                $query->where('tariffs.sale_price', '<=', (float) $priceTo);
            })
            ->when(isset($sortMap[$sort]), function ($query) use ($sortMap, $sort, $direction) {
                $query->orderBy($sortMap[$sort], $direction);
            }, function ($query) {
                $query->orderBy('tariffs.name');
            })
            ->paginate(20)
            ->withQueryString();

        $selectedExtraLabels = collect($extraPriceFilters)
            ->filter(fn ($key) => array_key_exists($key, $extraPriceOptions))
            ->map(fn ($key) => $extraPriceOptions[$key])
            ->values()
            ->all();

        $title = 'Прайс: роздрібна ціна';
        if (! empty($selectedExtraLabels)) {
            $title .= ' + '.implode(' + ', $selectedExtraLabels);
        }

        return view('tariffs.index', [
            'tariffs' => $tariffs,
            'subcontractors' => $subcontractors,
            'categories' => $categories,
            'productGroups' => $productGroups,
            'extraPriceOptions' => $extraPriceOptions,
            'selectedExtraPrices' => $extraPriceFilters,
            'title' => $title,
            'filters' => [
                'subcontractors' => $subcontractorIds,
                'category' => $category,
                'product_group_id' => $productGroupId,
                'search' => $search,
                'price_from' => $priceFrom,
                'price_to' => $priceTo,
                'extra_prices' => $extraPriceFilters,
            ],
        ]);
    }

    public function show(Tariff $tariff): View
    {
        $subcontractors = Subcontractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $productCategories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $productGroups = ProductGroup::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $crossLinks = $tariff->crossLinks()->with('supplier')->get();
        $childInternalCodes = $crossLinks->pluck('child_internal_code')->all();
        $historyCodes = array_values(array_unique(array_merge([$tariff->internal_code], $childInternalCodes)));

        $history = PricingHistory::query()
            ->with('supplier')
            ->whereIn('internal_code', $historyCodes)
            ->orderByDesc('changed_at')
            ->get();

        $historyCodesWithEntries = $history->pluck('internal_code')->unique()->all();
        $missingHistoryCodes = array_values(array_diff($historyCodes, $historyCodesWithEntries));

        if (! empty($missingHistoryCodes)) {
            $fallbackItems = PurchaseItem::query()
                ->with('supplier')
                ->whereIn('internal_code', $missingHistoryCodes)
                ->orderByDesc('imported_at')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'id' => null,
                        'changed_at' => $item->imported_at,
                        'import_price' => $item->price_vat,
                        'markup_percent' => null,
                        'markup_price' => null,
                        'markup_wholesale_percent' => null,
                        'wholesale_price' => null,
                        'markup_vip_percent' => null,
                        'vip_price' => null,
                        'internal_code' => $item->internal_code,
                        'supplier' => $item->supplier,
                        'user' => null,
                    ];
                });

            $history = $history->concat($fallbackItems)
                ->sortByDesc(fn ($row) => $row->changed_at ?? now())
                ->values();
        }

        $parentSupplierId = PurchaseItem::query()
            ->where('internal_code', $tariff->internal_code)
            ->orderByDesc('imported_at')
            ->value('supplier_id');

        $pricingCodes = PricingItem::query()
            ->pluck('internal_code')
            ->all();

        $tariffCodes = Tariff::query()
            ->pluck('internal_code')
            ->all();

        $allowedInternalCodes = array_values(array_unique(array_merge($pricingCodes, $tariffCodes)));

        $latestItems = PurchaseItem::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('supplier_id', 'internal_code');

        $supplierItems = PurchaseItem::query()
            ->joinSub($latestItems, 'latest_items', function ($join) {
                $join->on('purchase_items.id', '=', 'latest_items.id');
            })
            ->with('supplier')
            ->whereNotNull('purchase_items.internal_code')
            ->where('purchase_items.internal_code', '!=', '')
            ->whereNotNull('purchase_items.name')
            ->where('purchase_items.name', '!=', '')
            ->when(! empty($allowedInternalCodes), function ($query) use ($allowedInternalCodes) {
                $query->whereIn('purchase_items.internal_code', $allowedInternalCodes);
            }, function ($query) {
                $query->whereRaw('1=0');
            })
            ->when($parentSupplierId, function ($query) use ($parentSupplierId) {
                $query->where('purchase_items.supplier_id', '!=', $parentSupplierId);
            })
            ->when(! empty($childInternalCodes), function ($query) use ($childInternalCodes) {
                $query->whereNotIn('purchase_items.internal_code', $childInternalCodes);
            })
            ->orderBy('purchase_items.name')
            ->get();

        $availableSuppliers = Supplier::query()
            ->whereIn('id', $supplierItems->pluck('supplier_id')->unique()->all())
            ->orderBy('name')
            ->get();

        return view('tariffs.show', [
            'tariff' => $tariff,
            'subcontractors' => $subcontractors,
            'productCategories' => $productCategories,
            'productGroups' => $productGroups,
            'history' => $history,
            'crossLinks' => $crossLinks,
            'availableSuppliers' => $availableSuppliers,
            'supplierItems' => $supplierItems,
            'hasCrossLinks' => $crossLinks->isNotEmpty(),
        ]);
    }

    public function storeCrossLink(Request $request, Tariff $tariff): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'child_internal_code' => ['required', 'string'],
        ]);

        if ($data['child_internal_code'] === $tariff->internal_code) {
            return redirect()
                ->route('tariffs.show', $tariff)
                ->withErrors(['cross_link' => __('Неможливо привʼязати товар до самого себе.')]);
        }

        $existsInSupplier = PurchaseItem::query()
            ->where('supplier_id', $data['supplier_id'])
            ->where('internal_code', $data['child_internal_code'])
            ->exists();

        if (! $existsInSupplier) {
            return redirect()
                ->route('tariffs.show', $tariff)
                ->withErrors(['cross_link' => __('Обраний товар не належить цьому постачальнику.')]);
        }

        $existing = TariffCrossLink::query()
            ->where('child_internal_code', $data['child_internal_code'])
            ->first();

        if ($existing && $existing->parent_tariff_id !== $tariff->id) {
            return redirect()
                ->route('tariffs.show', $tariff)
                ->withErrors(['cross_link' => __('Ця позиція вже привʼязана до іншого товару.')]);
        }

        TariffCrossLink::firstOrCreate(
            [
                'parent_tariff_id' => $tariff->id,
                'child_internal_code' => $data['child_internal_code'],
            ],
            [
                'child_supplier_id' => $data['supplier_id'],
                'created_by' => $request->user()?->id,
            ]
        );

        PricingItem::query()
            ->where('internal_code', $data['child_internal_code'])
            ->delete();

        return redirect()
            ->route('tariffs.show', $tariff)
            ->with('status', __('Кросс-звʼязок збережено.'));
    }

    public function update(Request $request, Tariff $tariff): RedirectResponse
    {
        $categoryOptions = ProductCategory::query()->pluck('name')->all();
        $payload = $request->all();
        $productGroupName = trim((string) ($payload['product_group_name'] ?? ''));

        if ($productGroupName !== '') {
            $payload['product_group_id'] = ProductGroup::query()
                ->where('name', $productGroupName)
                ->value('id');
        } elseif (! $request->filled('product_group_id')) {
            $payload['product_group_id'] = null;
        }

        $data = Validator::make($payload, [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255', Rule::in($categoryOptions)],
            'product_group_id' => ['nullable', 'integer', 'exists:product_groups,id'],
            'film_brand_series' => ['nullable', 'string', 'max:255'],
            'roll_width_m' => ['nullable', 'numeric', 'min:0'],
            'roll_length_m' => ['nullable', 'numeric', 'min:0'],
            'sheet_thickness_mm' => ['nullable', 'numeric', 'min:0'],
            'sheet_width_mm' => ['nullable', 'numeric', 'min:0'],
            'sheet_length_mm' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:255'],
            'finish' => ['nullable', 'string', 'max:255'],
            'special_effect' => ['nullable', 'string', 'max:255'],
            'liner' => ['nullable', 'string', 'max:255'],
            'double_sided' => ['nullable', 'string', 'max:255'],
            'subcontractor_id' => ['nullable', 'integer', 'exists:subcontractors,id'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'urgent_price' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        foreach (['roll_width_m', 'roll_length_m', 'sheet_thickness_mm'] as $dimensionField) {
            if (array_key_exists($dimensionField, $data) && $data[$dimensionField] !== null && $data[$dimensionField] !== '') {
                $data[$dimensionField] = round((float) $data[$dimensionField], 2);
            }
        }

        $tariff->update($data);

        $importPrice = $tariff->purchase_price;
        $markupPercent = null;
        $markupWholesalePercent = null;
        $markupVipPercent = null;
        if ($importPrice !== null && (float) $importPrice > 0 && $tariff->sale_price !== null) {
            $markupPercent = ((float) $tariff->sale_price / (float) $importPrice - 1) * 100;
        }
        if ($importPrice !== null && (float) $importPrice > 0 && $tariff->wholesale_price !== null) {
            $markupWholesalePercent = ((float) $tariff->wholesale_price / (float) $importPrice - 1) * 100;
        }
        if ($importPrice !== null && (float) $importPrice > 0 && $tariff->urgent_price !== null) {
            $markupVipPercent = ((float) $tariff->urgent_price / (float) $importPrice - 1) * 100;
        }

        PricingHistory::create([
            'internal_code' => $tariff->internal_code,
            'name' => $tariff->name,
            'category' => $tariff->category,
            'supplier_id' => null,
            'subcontractor_id' => $tariff->subcontractor_id,
            'import_price' => $importPrice,
            'markup_percent' => $markupPercent,
            'markup_price' => $tariff->sale_price,
            'markup_wholesale_percent' => $markupWholesalePercent,
            'wholesale_price' => $tariff->wholesale_price,
            'markup_vip_percent' => $markupVipPercent,
            'vip_price' => $tariff->urgent_price,
            'changed_by' => $request->user()?->id,
            'changed_at' => now(),
            'source' => 'tariff',
        ]);

        return redirect()
            ->route('tariffs.show', $tariff)
            ->with('status', __('Tariff updated.'));
    }

    public function revertHistory(Request $request, Tariff $tariff, PricingHistory $history): RedirectResponse
    {
        $allowedCodes = array_merge(
            [$tariff->internal_code],
            $tariff->crossLinks()->pluck('child_internal_code')->all()
        );

        if (! in_array($history->internal_code, $allowedCodes, true)) {
            return redirect()
                ->route('tariffs.show', $tariff)
                ->withErrors(['history' => __('Обраний запис історії не належить цій картці товару.')]);
        }

        $tariff->update([
            'sale_price' => $history->markup_price,
            'wholesale_price' => $history->wholesale_price,
            'urgent_price' => $history->vip_price,
        ]);

        PricingHistory::create([
            'internal_code' => $history->internal_code,
            'name' => $history->name,
            'category' => $history->category,
            'supplier_id' => null,
            'subcontractor_id' => $history->subcontractor_id,
            'import_price' => $history->import_price,
            'markup_percent' => $history->markup_percent,
            'markup_price' => $history->markup_price,
            'markup_wholesale_percent' => $history->markup_wholesale_percent,
            'wholesale_price' => $history->wholesale_price,
            'markup_vip_percent' => $history->markup_vip_percent,
            'vip_price' => $history->vip_price,
            'changed_by' => $request->user()?->id,
            'changed_at' => now(),
            'source' => 'revert',
        ]);

        return redirect()
            ->route('tariffs.show', $tariff)
            ->with('status', __('Ціни товару оновлено з обраного запису історії.'));
    }

    public function deactivate(Request $request, Tariff $tariff): RedirectResponse
    {
        $tariff->update(['is_active' => false]);

        $latestPurchase = PurchaseItem::query()
            ->where('internal_code', $tariff->internal_code)
            ->orderByDesc('imported_at')
            ->first();

        $importPrice = $latestPurchase?->price_vat ?? $tariff->purchase_price;

        $markupPercent = 50.0;
        $markupWholesalePercent = 30.0;
        $markupVipPercent = 40.0;

        if ($importPrice !== null && (float) $importPrice > 0) {
            if ($tariff->sale_price !== null) {
                $markupPercent = (((float) $tariff->sale_price / (float) $importPrice) - 1) * 100;
            }
            if ($tariff->wholesale_price !== null) {
                $markupWholesalePercent = (((float) $tariff->wholesale_price / (float) $importPrice) - 1) * 100;
            }
            if ($tariff->urgent_price !== null) {
                $markupVipPercent = (((float) $tariff->urgent_price / (float) $importPrice) - 1) * 100;
            }
        }

        $resolvedMarkupPrice = $tariff->sale_price;
        if ($resolvedMarkupPrice === null && $importPrice !== null) {
            $resolvedMarkupPrice = (float) $importPrice * (1 + ($markupPercent / 100));
        }

        $resolvedWholesalePrice = $tariff->wholesale_price;
        if ($resolvedWholesalePrice === null && $importPrice !== null) {
            $resolvedWholesalePrice = (float) $importPrice * (1 + ($markupWholesalePercent / 100));
        }

        $resolvedVipPrice = $tariff->urgent_price;
        if ($resolvedVipPrice === null && $importPrice !== null) {
            $resolvedVipPrice = (float) $importPrice * (1 + ($markupVipPercent / 100));
        }

        PricingItem::updateOrCreate(
            ['internal_code' => $tariff->internal_code],
            [
                'external_code' => $latestPurchase?->external_code,
                'name' => $tariff->name,
                'category' => $tariff->category,
                'product_group_id' => $tariff->product_group_id,
                'unit' => $latestPurchase?->unit,
                'supplier_id' => $latestPurchase?->supplier_id,
                'subcontractor_id' => $tariff->subcontractor_id,
                'import_price' => $importPrice,
                'markup_percent' => $markupPercent,
                'markup_price' => $resolvedMarkupPrice,
                'markup_wholesale_percent' => $markupWholesalePercent,
                'wholesale_price' => $resolvedWholesalePrice,
                'markup_vip_percent' => $markupVipPercent,
                'vip_price' => $resolvedVipPrice,
                'last_changed_at' => now(),
                'last_imported_at' => $latestPurchase?->imported_at ?? now(),
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('tariffs.index')
            ->with('status', __('Tariff deactivated.'));
    }
}
