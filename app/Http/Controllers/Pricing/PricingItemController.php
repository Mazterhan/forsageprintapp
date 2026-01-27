<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingHistory;
use App\Models\PricingItem;
use App\Models\Subcontractor;
use App\Models\Tariff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingItemController extends Controller
{
    public function show(PricingItem $pricingItem): View
    {
        $subcontractors = Subcontractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $history = PricingHistory::query()
            ->where('internal_code', $pricingItem->internal_code)
            ->orderByDesc('changed_at')
            ->get();

        return view('pricing.items.show', [
            'item' => $pricingItem,
            'subcontractors' => $subcontractors,
            'history' => $history,
        ]);
    }

    public function update(Request $request, PricingItem $pricingItem): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'subcontractor_id' => ['nullable', 'integer', 'exists:subcontractors,id'],
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'markup_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $markupPercent = $data['markup_percent'] ?? $pricingItem->markup_percent;
        $markupPrice = $data['markup_price'] ?? null;

        if ($markupPrice === null && $pricingItem->import_price !== null) {
            $markupPrice = $pricingItem->import_price * (1 + ($markupPercent / 100));
        }

        $pricingItem->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'subcontractor_id' => $data['subcontractor_id'] ?? null,
            'markup_percent' => $markupPercent,
            'markup_price' => $markupPrice,
            'last_changed_at' => now(),
        ]);

        $existingTariff = Tariff::query()
            ->where('internal_code', $pricingItem->internal_code)
            ->first();

        $resolvedCategory = $pricingItem->category ?? $existingTariff?->category;
        $resolvedSubcontractor = $pricingItem->subcontractor_id ?? $existingTariff?->subcontractor_id;

        Tariff::updateOrCreate(
            ['internal_code' => $pricingItem->internal_code],
            [
                'name' => $pricingItem->name,
                'category' => $resolvedCategory,
                'subcontractor_id' => $resolvedSubcontractor,
                'purchase_price' => $pricingItem->import_price,
                'sale_price' => $pricingItem->markup_price,
                'is_active' => true,
            ]
        );

        PricingHistory::create([
            'internal_code' => $pricingItem->internal_code,
            'name' => $pricingItem->name,
            'category' => $pricingItem->category,
            'supplier_id' => $pricingItem->supplier_id,
            'subcontractor_id' => $pricingItem->subcontractor_id,
            'import_price' => $pricingItem->import_price,
            'markup_percent' => $pricingItem->markup_percent,
            'markup_price' => $pricingItem->markup_price,
            'changed_by' => $request->user()?->id,
            'changed_at' => now(),
            'source' => 'apply',
        ]);

        $pricingItem->delete();

        return redirect()
            ->route('pricing.index')
            ->with('status', __('Pricing item applied.'));
    }
}
