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
        $sort = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'name' => 'pricing_items.name',
            'product_group' => 'product_groups.name',
            'subcontractor' => 'subcontractors.name',
            'import_price' => 'pricing_items.import_price',
            'imported_at' => 'pricing_items.last_changed_at',
        ];

        $items = PricingItem::query()
            ->leftJoin('subcontractors', 'subcontractors.id', '=', 'pricing_items.subcontractor_id')
            ->leftJoin('product_groups', 'product_groups.id', '=', 'pricing_items.product_group_id')
            ->select('pricing_items.*')
            ->with(['subcontractor', 'supplier', 'productGroup'])
            ->where('pricing_items.is_active', true)
            ->when(isset($sortMap[$sort]), function ($query) use ($sortMap, $sort, $direction) {
                $query->orderBy($sortMap[$sort], $direction);
            }, function ($query) {
                $query->orderByDesc('pricing_items.last_changed_at');
            })
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
            'markup_wholesale_percent' => $request->input("markup_wholesale_percent.$itemId"),
            'wholesale_price' => $request->input("wholesale_price.$itemId"),
            'markup_vip_percent' => $request->input("markup_vip_percent.$itemId"),
            'vip_price' => $request->input("vip_price.$itemId"),
            'subcontractor_id' => $request->input("subcontractor_id.$itemId"),
        ];

        $validator = Validator::make($data, [
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'markup_price' => ['nullable', 'numeric', 'min:0'],
            'markup_wholesale_percent' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'markup_vip_percent' => ['nullable', 'numeric', 'min:0'],
            'vip_price' => ['nullable', 'numeric', 'min:0'],
            'subcontractor_id' => ['nullable', 'integer', 'exists:subcontractors,id'],
        ]);

        return $validator->validate();
    }

    private function applyPricingItem(PricingItem $pricingItem, array $data, ?int $userId): void
    {
        $markupPercent = $data['markup_percent'] ?? $pricingItem->markup_percent;
        $markupPrice = $data['markup_price'] ?? null;
        $markupWholesalePercent = $data['markup_wholesale_percent'] ?? $pricingItem->markup_wholesale_percent;
        $wholesalePrice = $data['wholesale_price'] ?? null;
        $markupVipPercent = $data['markup_vip_percent'] ?? $pricingItem->markup_vip_percent;
        $vipPrice = $data['vip_price'] ?? null;

        if ($markupPrice === null && $pricingItem->import_price !== null) {
            $markupPrice = $pricingItem->import_price * (1 + ($markupPercent / 100));
        }
        if ($wholesalePrice === null && $pricingItem->import_price !== null) {
            $wholesalePrice = $pricingItem->import_price * (1 + ($markupWholesalePercent / 100));
        }
        if ($vipPrice === null && $pricingItem->import_price !== null) {
            $vipPrice = $pricingItem->import_price * (1 + ($markupVipPercent / 100));
        }

        $pricingItem->update([
            'markup_percent' => $markupPercent,
            'markup_price' => $markupPrice,
            'markup_wholesale_percent' => $markupWholesalePercent,
            'wholesale_price' => $wholesalePrice,
            'markup_vip_percent' => $markupVipPercent,
            'vip_price' => $vipPrice,
            'subcontractor_id' => $data['subcontractor_id'] ?? $pricingItem->subcontractor_id,
            'last_changed_at' => now(),
        ]);

        $existingTariff = Tariff::query()
            ->where('internal_code', $pricingItem->internal_code)
            ->first();

        $resolvedCategory = $pricingItem->category ?? $existingTariff?->category;
        $resolvedProductGroup = $pricingItem->product_group_id ?? $existingTariff?->product_group_id;
        $resolvedSubcontractor = $pricingItem->subcontractor_id ?? $existingTariff?->subcontractor_id;

        Tariff::updateOrCreate(
            ['internal_code' => $pricingItem->internal_code],
            [
                'name' => $pricingItem->name,
                'category' => $resolvedCategory,
                'product_group_id' => $resolvedProductGroup,
                'subcontractor_id' => $resolvedSubcontractor,
                'purchase_price' => $pricingItem->import_price,
                'sale_price' => $pricingItem->markup_price,
                'wholesale_price' => $pricingItem->wholesale_price,
                'urgent_price' => $pricingItem->vip_price,
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
            'markup_wholesale_percent' => $pricingItem->markup_wholesale_percent,
            'wholesale_price' => $pricingItem->wholesale_price,
            'markup_vip_percent' => $pricingItem->markup_vip_percent,
            'vip_price' => $pricingItem->vip_price,
            'changed_by' => $userId,
            'changed_at' => now(),
            'source' => 'apply',
        ]);

        $pricingItem->delete();
    }
}
