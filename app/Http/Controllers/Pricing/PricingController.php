<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingHistory;
use App\Models\PricingItem;
use App\Models\Subcontractor;
use App\Models\Tariff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(Request $request): View
    {
        $items = PricingItem::query()
            ->with(['subcontractor', 'supplier'])
            ->where('is_active', true)
            ->orderByDesc('last_changed_at')
            ->paginate(20)
            ->withQueryString();

        $subcontractors = Subcontractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pricing.index', [
            'items' => $items,
            'subcontractors' => $subcontractors,
        ]);
    }

    public function applySingle(Request $request, PricingItem $pricingItem): RedirectResponse
    {
        $data = $this->extractApplyData($request, $pricingItem->id);
        $this->applyPricingItem($pricingItem, $data, $request->user()?->id);

        return redirect()
            ->route('pricing.index')
            ->with('status', __('Pricing item applied.'));
    }

    public function applyBulk(Request $request): RedirectResponse
    {
        $ids = array_filter((array) $request->input('selected', []));

        if (empty($ids)) {
            return redirect()
                ->route('pricing.index')
                ->withErrors(['selected' => __('Select at least one item.')]);
        }

        $items = PricingItem::query()
            ->whereIn('id', $ids)
            ->get();

        foreach ($items as $item) {
            $data = $this->extractApplyData($request, $item->id);
            $this->applyPricingItem($item, $data, $request->user()?->id);
        }

        return redirect()
            ->route('pricing.index')
            ->with('status', __('Selected items applied.'));
    }

    public function deactivate(PricingItem $pricingItem): RedirectResponse
    {
        $pricingItem->update(['is_active' => false]);

        return redirect()
            ->route('pricing.index')
            ->with('status', __('Pricing item deactivated.'));
    }

    private function extractApplyData(Request $request, int $itemId): array
    {
        $data = [
            'markup_percent' => $request->input("markup_percent.$itemId"),
            'markup_price' => $request->input("markup_price.$itemId"),
            'subcontractor_id' => $request->input("subcontractor_id.$itemId"),
        ];

        $validator = Validator::make($data, [
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'markup_price' => ['nullable', 'numeric', 'min:0'],
            'subcontractor_id' => ['nullable', 'integer', 'exists:subcontractors,id'],
        ]);

        return $validator->validate();
    }

    private function applyPricingItem(PricingItem $pricingItem, array $data, ?int $userId): void
    {
        $markupPercent = $data['markup_percent'] ?? $pricingItem->markup_percent;
        $markupPrice = $data['markup_price'] ?? null;

        if ($markupPrice === null && $pricingItem->import_price !== null) {
            $markupPrice = $pricingItem->import_price * (1 + ($markupPercent / 100));
        }

        $pricingItem->update([
            'markup_percent' => $markupPercent,
            'markup_price' => $markupPrice,
            'subcontractor_id' => $data['subcontractor_id'] ?? $pricingItem->subcontractor_id,
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
            'changed_by' => $userId,
            'changed_at' => now(),
            'source' => 'apply',
        ]);

        $pricingItem->delete();
    }
}
