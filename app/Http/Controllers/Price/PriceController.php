<?php

namespace App\Http\Controllers\Price;

use App\Http\Controllers\Controller;
use App\Models\PriceItem;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PriceController extends Controller
{
    public function index(Request $request): View
    {
        $sort = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'internal_code' => 'internal_code',
            'name' => 'name',
            'category' => 'category',
            'purchase_price' => 'purchase_price',
            'service_price' => 'service_price',
        ];

        $items = PriceItem::query()
            ->when(isset($sortMap[$sort]), function ($query) use ($sortMap, $sort, $direction) {
                $query->orderBy($sortMap[$sort], $direction);
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate(20)
            ->withQueryString();

        return view('price.index', [
            'items' => $items,
            'title' => __('Прайс2: роздрібна ціна'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        $categories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'material_type']);

        return view('price.create', [
            'categories' => $categories,
            'title' => __('Нова позиція'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $categoryMap = ProductCategory::query()
            ->pluck('material_type', 'name')
            ->toArray();

        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('price_items', 'name')],
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
                if ($category === '' || ! array_key_exists($category, $categoryMap)) {
                    $validator->errors()->add('category', __('Оберіть коректну категорію.'));
                }
                if ($request->input('service_price') === null || $request->input('service_price') === '') {
                    $validator->errors()->add('service_price', __('Поле "Вартість послуги" є обовʼязковим для моделі "Матеріал".'));
                }
            }
        })->validate();

        $modelType = (string) $data['model_type'];
        $category = $modelType === 'Матеріал' ? trim((string) ($data['category'] ?? '')) : null;
        $materialType = $category ? ($categoryMap[$category] ?? null) : null;

        PriceItem::create([
            'internal_code' => $this->generateInternalCode(),
            'name' => $data['name'],
            'model_type' => $modelType,
            'category' => $category ?: null,
            'material_type' => $materialType,
            'internal_name' => $category ?: null,
            'service_price' => $data['service_price'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? null,
            'measurement_unit' => $data['measurement_unit'] ?? null,
            'for_customer_material' => $modelType === 'Послуга' ? (bool) ($data['for_customer_material'] ?? false) : false,
            'width_m' => $modelType === 'Матеріал' ? ($data['width_m'] ?? null) : null,
            'length_m' => $modelType === 'Матеріал' ? ($data['length_m'] ?? null) : null,
            'thickness_mm' => $modelType === 'Матеріал' ? ($data['thickness_mm'] ?? null) : null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('price.index')
            ->with('status', __('Позицію додано.'));
    }

    public function show(PriceItem $priceItem): View
    {
        return view('price.show', [
            'item' => $priceItem,
            'title' => $priceItem->name ?: __('Позиція'),
        ]);
    }

    public function update(Request $request, PriceItem $priceItem): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'service_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
        ])->validate();

        $priceItem->update([
            'service_price' => $data['service_price'],
            'purchase_price' => $data['purchase_price'],
        ]);

        return redirect()
            ->route('price.show', $priceItem)
            ->with('status', __('Позицію оновлено.'));
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

    private function generateInternalCode(): string
    {
        $next = 1;
        $last = PriceItem::query()
            ->where('internal_code', 'like', 'MAN-1-%')
            ->orderByDesc('id')
            ->value('internal_code');

        if (is_string($last) && preg_match('/^MAN-1-(\d{8})$/', $last, $matches) === 1) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $code = 'MAN-1-'.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
            $next++;
        } while (PriceItem::query()->where('internal_code', $code)->exists());

        return $code;
    }
}
