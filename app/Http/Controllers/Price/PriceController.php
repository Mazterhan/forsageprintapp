<?php

namespace App\Http\Controllers\Price;

use App\Http\Controllers\Controller;
use App\Models\PriceItem;
use App\Models\PriceItemHistory;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PriceController extends Controller
{
    public function index(Request $request): View
    {
        $sort = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $status = trim((string) $request->query('status', ''));
        $modelType = trim((string) $request->query('model_type', ''));

        $sortMap = [
            'internal_code' => 'internal_code',
            'name' => 'name',
            'category' => 'category',
            'purchase_price' => 'purchase_price',
            'service_price' => 'service_price',
        ];

        $items = PriceItem::query()
            ->where('visible', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('internal_code', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', function ($query) use ($category) {
                if ($category === 'Послуга') {
                    $query->where(function ($inner) {
                        $inner->where('category', 'Послуга')
                            ->orWhere(function ($legacy) {
                                $legacy->where('model_type', 'Послуга')
                                    ->where(function ($emptyCategory) {
                                        $emptyCategory->whereNull('category')
                                            ->orWhere('category', '');
                                    });
                            });
                    });
                    return;
                }
                $query->where('category', $category);
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->when($modelType !== '', function ($query) use ($modelType) {
                $query->where('model_type', $modelType);
            })
            ->when(isset($sortMap[$sort]), function ($query) use ($sortMap, $sort, $direction) {
                $query->orderBy($sortMap[$sort], $direction);
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate(20)
            ->withQueryString();

        $categoryOptions = PriceItem::query()
            ->where('visible', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->all();

        $hasLegacyServiceWithoutCategory = PriceItem::query()
            ->where('visible', true)
            ->where('model_type', 'Послуга')
            ->where(function ($query) {
                $query->whereNull('category')
                    ->orWhere('category', '');
            })
            ->exists();
        if ($hasLegacyServiceWithoutCategory && ! in_array('Послуга', $categoryOptions, true)) {
            $categoryOptions[] = 'Послуга';
            sort($categoryOptions, SORT_STRING);
        }

        $modelTypeOptions = PriceItem::query()
            ->where('visible', true)
            ->whereNotNull('model_type')
            ->orderBy('model_type')
            ->distinct()
            ->pluck('model_type')
            ->all();

        return view('price.index', [
            'items' => $items,
            'title' => __('Прайс2: роздрібна ціна'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'modelType' => $modelType,
            'categoryOptions' => $categoryOptions,
            'modelTypeOptions' => $modelTypeOptions,
        ]);
    }

    public function create(): View
    {
        $categories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'material_type', 'code']);

        return view('price.create', [
            'categories' => $categories,
            'title' => __('Нова позиція'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $categoryMap = ProductCategory::query()
            ->get(['name', 'material_type', 'code'])
            ->keyBy('name');

        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'model_type' => ['required', 'string', 'in:Матеріал,Послуга'],
            'category' => ['nullable', 'string', 'max:255'],
            'service_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'measurement_unit' => ['required', 'string', 'in:м2,шт.,м.п.'],
            'for_customer_material' => ['nullable', 'boolean'],
            'width_m' => ['nullable', 'numeric', 'min:0'],
            'length_m' => ['nullable', 'numeric', 'min:0'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0'],
        ])->after(function ($validator) use ($request, $categoryMap) {
            $modelType = (string) $request->input('model_type');
            $category = trim((string) $request->input('category'));

            if ($modelType === 'Матеріал') {
                if ($category === '' || ! $categoryMap->has($category)) {
                    $validator->errors()->add('category', __('Оберіть коректну категорію.'));
                } elseif (trim((string) $categoryMap->get($category)?->code) === '') {
                    $validator->errors()->add('category', __('Для обраної категорії не задано код.'));
                }
                if ($request->input('service_price') === null || $request->input('service_price') === '') {
                    $validator->errors()->add('service_price', __('Поле "Вартість послуги" є обовʼязковим для моделі "Матеріал".'));
                }
            }

            $name = trim((string) $request->input('name'));
            if ($name !== '') {
                $nameAlreadyUsed = PriceItem::query()
                    ->where('name', $name)
                    ->where(function ($query): void {
                        $query->where('visible', true)
                            ->orWhere('is_active', true);
                    })
                    ->exists();

                if ($nameAlreadyUsed) {
                    $validator->errors()->add('name', __('Позиція з такою назвою вже існує.'));
                }
            }
        })->validate();

        $modelType = (string) $data['model_type'];
        $category = $modelType === 'Матеріал'
            ? trim((string) ($data['category'] ?? ''))
            : 'Послуга';
        $categoryRecord = $category ? $categoryMap->get($category) : null;
        $materialType = $categoryRecord?->material_type;
        $categoryCode = $categoryRecord?->code ? strtoupper(trim((string) $categoryRecord->code)) : null;
        $forCustomerMaterial = $modelType === 'Послуга' ? (bool) ($data['for_customer_material'] ?? false) : false;

        DB::transaction(function () use ($data, $modelType, $category, $materialType, $categoryCode, $forCustomerMaterial): void {
            $name = trim((string) $data['name']);
            $nameAlreadyUsed = PriceItem::query()
                ->where('name', $name)
                ->where(function ($query): void {
                    $query->where('visible', true)
                        ->orWhere('is_active', true);
                })
                ->lockForUpdate()
                ->exists();

            if ($nameAlreadyUsed) {
                throw ValidationException::withMessages([
                    'name' => __('Позиція з такою назвою вже існує.'),
                ]);
            }

            PriceItem::create([
                'internal_code' => $this->generateInternalCode($modelType, $categoryCode, $forCustomerMaterial),
                'name' => $name,
                'model_type' => $modelType,
                'category' => $category ?: null,
                'material_type' => $materialType,
                'internal_name' => $modelType === 'Матеріал' ? ($category ?: null) : null,
                'service_price' => $data['service_price'] ?? null,
                'purchase_price' => $data['purchase_price'] ?? null,
                'measurement_unit' => $data['measurement_unit'] ?? null,
                'for_customer_material' => $forCustomerMaterial,
                'width_m' => $modelType === 'Матеріал' ? ($data['width_m'] ?? null) : null,
                'length_m' => $modelType === 'Матеріал' ? ($data['length_m'] ?? null) : null,
                'thickness_mm' => $modelType === 'Матеріал' ? ($data['thickness_mm'] ?? null) : null,
                'is_active' => true,
                'visible' => true,
            ]);
        });

        return redirect()
            ->route('price.index')
            ->with('status', __('Позицію додано.'));
    }

    public function show(PriceItem $priceItem): View
    {
        $priceItem->load(['histories.user']);

        return view('price.show', [
            'item' => $priceItem,
            'title' => $priceItem->name ?: __('Позиція'),
            'history' => $priceItem->histories,
        ]);
    }

    public function update(Request $request, PriceItem $priceItem): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'service_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $newServicePrice = $this->parseDecimal($data['service_price']);
        $newPurchasePrice = $this->parseDecimal($data['purchase_price']);
        $hasChanges = $this->hasPriceChanges($priceItem, $newServicePrice, $newPurchasePrice);

        if ($hasChanges) {
            $priceItem->update([
                'service_price' => $newServicePrice,
                'purchase_price' => $newPurchasePrice,
            ]);

            $this->recordHistory($priceItem, $newServicePrice, $newPurchasePrice, (int) $request->user()->id);
        }

        return redirect()
            ->route('price.show', $priceItem)
            ->with('status', __('Позицію оновлено.'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $payload = $request->input('items', []);
        if (!is_array($payload) || $payload === []) {
            return redirect()->route('price.index');
        }

        $itemIds = array_map('intval', array_keys($payload));
        $items = PriceItem::query()
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        foreach ($payload as $id => $values) {
            $id = (int) $id;
            if (!isset($items[$id]) || !is_array($values)) {
                continue;
            }

            $item = $items[$id];
            $validated = Validator::make($values, [
                'service_price' => ['nullable', 'numeric', 'min:0'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
            ])->validate();

            $newServicePrice = $this->parseDecimal($validated['service_price']);
            $newPurchasePrice = $this->parseDecimal($validated['purchase_price']);
            $hasChanges = $this->hasPriceChanges($item, $newServicePrice, $newPurchasePrice);

            if (! $hasChanges) {
                continue;
            }

            $item->update([
                'service_price' => $newServicePrice,
                'purchase_price' => $newPurchasePrice,
            ]);

            $this->recordHistory($item, $newServicePrice, $newPurchasePrice, (int) $request->user()->id);
        }

        return redirect()
            ->route('price.index')
            ->with('status', __('Зміни збережено.'));
    }

    public function revertHistory(Request $request, PriceItem $priceItem, PriceItemHistory $history): RedirectResponse
    {
        if ($history->price_item_id !== $priceItem->id) {
            abort(404);
        }

        $priceItem->update([
            'service_price' => $history->service_price,
            'purchase_price' => $history->purchase_price,
        ]);

        $this->recordHistory(
            $priceItem,
            (float) $history->service_price,
            (float) $history->purchase_price,
            (int) $request->user()->id
        );

        return redirect()
            ->route('price.show', $priceItem)
            ->with('status', __('Ціну оновлено з історії.'));
    }

    public function toggle(PriceItem $priceItem): RedirectResponse
    {
        $priceItem->update([
            'is_active' => ! $priceItem->is_active,
        ]);

        return redirect()
            ->route('price.index')
            ->with('status', __('Статус позиції оновлено.'));
    }

    public function hide(PriceItem $priceItem): RedirectResponse
    {
        if ($priceItem->is_active) {
            return redirect()
                ->route('price.show', $priceItem)
                ->withErrors(['status' => __('Спочатку деактивуйте позицію.')]);
        }

        $priceItem->update([
            'visible' => false,
        ]);

        return redirect()
            ->route('price.index')
            ->with('status', __('Позицію приховано.'));
    }

    private function generateInternalCode(string $modelType, ?string $categoryCode, bool $forCustomerMaterial): string
    {
        if ($modelType === 'Матеріал') {
            $normalizedCategoryCode = strtoupper(trim((string) $categoryCode));
            $prefix = 'MAT-'.$normalizedCategoryCode.'-';
            $pattern = '/^MAT\-'.preg_quote($normalizedCategoryCode, '/').'-(\d{3})$/';

            $next = 1;
            $codes = PriceItem::query()
                ->where('internal_code', 'like', $prefix.'%')
                ->pluck('internal_code');

            foreach ($codes as $existingCode) {
                if (preg_match($pattern, (string) $existingCode, $matches) === 1) {
                    $next = max($next, ((int) $matches[1]) + 1);
                }
            }

            do {
                $code = $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
                $next++;
            } while (PriceItem::query()->where('internal_code', $code)->exists());

            return $code;
        }

        $suffix = $forCustomerMaterial ? '-MZ' : '';
        $likePattern = $forCustomerMaterial ? 'SERV-%-MZ' : 'SERV-%';
        $regex = $forCustomerMaterial ? '/^SERV-(\d{3})-MZ$/' : '/^SERV-(\d{3})$/';
        $next = 1;
        $codes = PriceItem::query()
            ->where('internal_code', 'like', $likePattern)
            ->pluck('internal_code');

        foreach ($codes as $existingCode) {
            if (preg_match($regex, (string) $existingCode, $matches) === 1) {
                $next = max($next, ((int) $matches[1]) + 1);
            }
        }

        do {
            $code = 'SERV-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT).$suffix;
            $next++;
        } while (PriceItem::query()->where('internal_code', $code)->exists());

        return $code;
    }

    private function hasPriceChanges(PriceItem $item, float $newServicePrice, float $newPurchasePrice): bool
    {
        return round((float) $item->service_price, 2) !== round($newServicePrice, 2)
            || round((float) $item->purchase_price, 2) !== round($newPurchasePrice, 2);
    }

    private function parseDecimal(mixed $value): float
    {
        return round((float) str_replace(',', '.', (string) $value), 2);
    }

    private function recordHistory(PriceItem $item, float $servicePrice, float $purchasePrice, int $userId): void
    {
        $markupPercent = null;
        if ($purchasePrice > 0) {
            $markupPercent = round((($servicePrice - $purchasePrice) / $purchasePrice) * 100, 2);
        }

        PriceItemHistory::query()->create([
            'price_item_id' => $item->id,
            'service_price' => $servicePrice,
            'purchase_price' => $purchasePrice,
            'markup_percent' => $markupPercent,
            'user_id' => $userId,
        ]);
    }
}
