<?php

namespace App\Http\Controllers\Tariffs;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTariffClientPriceRequest;
use App\Models\Client;
use App\Models\PricingHistory;
use App\Models\PricingItem;
use App\Models\PurchaseItem;
use App\Models\Subcontractor;
use App\Models\Tariff;
use App\Models\TariffClientPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TariffController extends Controller
{
    public function index(Request $request): View
    {
        $subcontractors = Subcontractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $subcontractorIds = array_filter((array) $request->query('subcontractors', []));
        $clientCategoryFilters = array_filter((array) $request->query('client_categories', []), fn ($value) => $value !== '');
        $category = (string) $request->query('category', '');
        $search = (string) $request->query('search', '');
        $priceFrom = $request->query('price_from');
        $priceTo = $request->query('price_to');

        $categories = Tariff::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $clientCategories = Client::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $tariffs = Tariff::query()
            ->with('subcontractor')
            ->where('is_active', true)
            ->when(! empty($subcontractorIds), function ($query) use ($subcontractorIds) {
                $query->whereIn('subcontractor_id', $subcontractorIds);
            })
            ->when($category !== '', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($priceFrom !== null && $priceFrom !== '', function ($query) use ($priceFrom) {
                $query->where('sale_price', '>=', (float) $priceFrom);
            })
            ->when($priceTo !== null && $priceTo !== '', function ($query) use ($priceTo) {
                $query->where('sale_price', '<=', (float) $priceTo);
            })
            ->when(! empty($clientCategoryFilters), function ($query) use ($clientCategoryFilters) {
                $query->with(['clientPrices' => function ($clientPrices) use ($clientCategoryFilters) {
                    $clientPrices->whereIn('client_category', $clientCategoryFilters);
                }]);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $title = empty($clientCategoryFilters)
            ? __('Tariffs. Retail price')
            : __('Tariffs. Retail + Client category price');

        return view('tariffs.index', [
            'tariffs' => $tariffs,
            'subcontractors' => $subcontractors,
            'categories' => $categories,
            'clientCategories' => $clientCategories,
            'selectedClientCategories' => $clientCategoryFilters,
            'title' => $title,
            'filters' => [
                'subcontractors' => $subcontractorIds,
                'category' => $category,
                'search' => $search,
                'price_from' => $priceFrom,
                'price_to' => $priceTo,
                'client_categories' => $clientCategoryFilters,
            ],
        ]);
    }

    public function show(Tariff $tariff): View
    {
        $tariff->load('clientPrices');

        $subcontractors = Subcontractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clientCategories = Client::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->merge($tariff->clientPrices->pluck('client_category'))
            ->unique()
            ->values();

        $history = PricingHistory::query()
            ->where('internal_code', $tariff->internal_code)
            ->orderByDesc('changed_at')
            ->get();

        return view('tariffs.show', [
            'tariff' => $tariff,
            'subcontractors' => $subcontractors,
            'clientCategories' => $clientCategories,
            'history' => $history,
        ]);
    }

    public function storeClientPrice(StoreTariffClientPriceRequest $request, Tariff $tariff): RedirectResponse
    {
        $data = $request->validated();

        TariffClientPrice::updateOrCreate(
            [
                'tariff_id' => $tariff->id,
                'client_category' => $data['client_category'],
            ],
            [
                'price' => round((float) $data['price'], 2),
            ]
        );

        return redirect()
            ->route('tariffs.show', $tariff)
            ->with('status', __('Client category price updated.'));
    }

    public function update(Request $request, Tariff $tariff): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'subcontractor_id' => ['nullable', 'integer', 'exists:subcontractors,id'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tariff->update($data);

        $importPrice = $tariff->purchase_price;
        $markupPercent = null;
        if ($importPrice !== null && (float) $importPrice > 0 && $tariff->sale_price !== null) {
            $markupPercent = ((float) $tariff->sale_price / (float) $importPrice - 1) * 100;
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
            'changed_by' => $request->user()?->id,
            'changed_at' => now(),
            'source' => 'tariff',
        ]);

        return redirect()
            ->route('tariffs.show', $tariff)
            ->with('status', __('Tariff updated.'));
    }

    public function deactivate(Request $request, Tariff $tariff): RedirectResponse
    {
        $tariff->update(['is_active' => false]);

        $latestPurchase = PurchaseItem::query()
            ->where('internal_code', $tariff->internal_code)
            ->orderByDesc('imported_at')
            ->first();

        if ($latestPurchase) {
            $existingPricing = PricingItem::query()
                ->where('internal_code', $tariff->internal_code)
                ->where('import_price', $latestPurchase->price_vat)
                ->first();

            if (! $existingPricing) {
                PricingItem::create([
                    'internal_code' => $latestPurchase->internal_code,
                    'external_code' => $latestPurchase->external_code,
                    'name' => $latestPurchase->name,
                    'category' => null,
                    'unit' => $latestPurchase->unit,
                    'supplier_id' => $latestPurchase->supplier_id,
                    'subcontractor_id' => $tariff->subcontractor_id,
                    'import_price' => $latestPurchase->price_vat,
                    'markup_percent' => 30,
                    'markup_price' => $latestPurchase->price_vat * 1.3,
                    'last_changed_at' => now(),
                    'last_imported_at' => $latestPurchase->imported_at,
                    'is_active' => true,
                ]);
            }
        }

        return redirect()
            ->route('tariffs.index')
            ->with('status', __('Tariff deactivated.'));
    }
}
