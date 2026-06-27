<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\OrderProposal;
use App\Models\OrderProposalEditLock;
use App\Models\PriceItem;
use App\Models\ProductCategory;
use App\Models\ProductTypeCategoryRule;
use App\Models\ProductType;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, PermissionService $permissions)
    {
        return view('orders.index', [
            'ordersPermissions' => [
                'calculation' => $permissions->can($request->user(), 'orders_calculation'),
                'proposals' => $permissions->can($request->user(), 'orders_proposals'),
                'clients' => $permissions->can($request->user(), 'orders_clients_manage'),
            ],
        ]);
    }

    public function calculation(Request $request, PermissionService $permissions)
    {
        $proposalPublicId = trim((string) $request->query('proposal', ''));
        $proposal = null;
        if ($proposalPublicId !== '') {
            $proposal = OrderProposal::query()
                ->with('editLock')
                ->whereNull('deleted_date')
                ->where('public_id', $proposalPublicId)
                ->first();

            if (!$proposal) {
                abort(404);
            }

            if (!$permissions->can($request->user(), 'orders_edit')) {
                abort(403);
            }

            if ($permissions->ordersListScope($request->user()) === 'own' && (int) $proposal->user_id !== (int) $request->user()?->id) {
                abort(403);
            }

            if ((bool) $proposal->is_autosaved) {
                abort(403);
            }

            $editToken = trim((string) $request->query('edit_token', ''));
            $lock = $proposal->editLock;
            if ($lock && !$lock->isActive()) {
                $lock->delete();
                $lock = null;
            }

            $sessionEditToken = (string) $request->session()->get("order_proposal_edit_tokens.{$proposal->id}", '');
            if (!$lock && $editToken !== '' && hash_equals($sessionEditToken, $editToken)) {
                $lock = OrderProposalEditLock::create([
                    'order_proposal_id' => $proposal->id,
                    'user_id' => $request->user()->id,
                    'lock_token' => $editToken,
                    'started_at' => now(),
                    'heartbeat_at' => now(),
                ]);
            }

            if (!$lock || $editToken === '' || (string) $lock->lock_token !== $editToken || (int) $lock->user_id !== (int) $request->user()?->id) {
                return redirect()
                    ->route('orders.proposals.show', $proposal)
                    ->with('status', 'Заявка заблокована для редагування. Відкрийте редагування зі сторінки заявки.');
            }

            $lock->update(['heartbeat_at' => now()]);
        }

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
            ->get(['internal_code', 'name', 'category', 'material_type', 'thickness_mm', 'service_price', 'purchase_price']);
        $rollingServiceItem = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->where('internal_code', 'SERV-003')
            ->first(['internal_code', 'name', 'service_price', 'purchase_price']);

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

        $materialPurchasePriceByMaterial = $materialItems
            ->groupBy(fn (PriceItem $item) => trim((string) $item->name))
            ->map(function ($items) {
                $price = $items
                    ->pluck('purchase_price')
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
                $materialPurchasePriceByMaterial[$rollingServiceName] = round((float) ($rollingServiceItem->purchase_price ?? 0), 2);
            }
        }

        $serviceItems = PriceItem::query()
            ->where('is_active', true)
            ->where('visible', true)
            ->whereIn('internal_code', [
                'SERV-001',
                'SERV-001-MZ',
                'SERV-002',
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
                'SERV-018',
                'SERV-019',
                'SERV-014',
            ])
            ->get(['internal_code', 'service_price', 'purchase_price']);

        $servicePriceByCode = $serviceItems
            ->pluck('service_price', 'internal_code')
            ->map(fn ($value) => round((float) ($value ?? 0), 2))
            ->toArray();

        $servicePurchasePriceByCode = $serviceItems
            ->pluck('purchase_price', 'internal_code')
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
            'materialPurchasePriceByMaterial' => $materialPurchasePriceByMaterial,
            'materialCodeByMaterial' => $materialCodeByMaterial,
            'servicePriceByCode' => $servicePriceByCode,
            'servicePurchasePriceByCode' => $servicePurchasePriceByCode,
            'typeCategoryMatrix' => $typeCategoryMatrix,
            'proposalId' => $proposal?->id,
            'initialState' => $proposal?->payload,
            'editLockToken' => isset($editToken) ? $editToken : null,
            'editLockHeartbeatUrl' => $proposal ? route('orders.proposals.edit-lock.heartbeat', $proposal) : null,
            'editLockReleaseUrl' => $proposal ? route('orders.proposals.edit-lock.release', $proposal) : null,
            'canSaveProposal' => $permissions->can($request->user(), 'orders_calc_save'),
            'showPurchaseFields' => $permissions->can($request->user(), 'orders_calc_purchase_visible'),
        ]);
    }

    public function saved(Request $request)
    {
        return view('orders.saved');
    }
}
