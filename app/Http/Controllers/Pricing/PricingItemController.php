<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingHistory;
use App\Models\PricingItem;
use App\Models\Subcontractor;
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
            'import_price' => ['nullable', 'numeric', 'min:0'],
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'markup_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $markupPercent = $data['markup_percent'] ?? $pricingItem->markup_percent;
        $markupPrice = $data['markup_price'] ?? null;

        if ($markupPrice === null && isset($data['import_price'])) {
            $markupPrice = $data['import_price'] * (1 + ($markupPercent / 100));
        }

        $pricingItem->update($data + [
            'markup_percent' => $markupPercent,
            'markup_price' => $markupPrice,
            'last_changed_at' => now(),
        ]);

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
            'source' => 'manual',
        ]);

        return redirect()
            ->route('pricing.items.show', $pricingItem)
            ->with('status', __('Pricing item updated.'));
    }
}
