<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class EditGroupsAndCategoriesController extends Controller
{
    public function productGroups(): View
    {
        $groups = ProductGroup::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();

        return view('admin.editgroupsandcategories.product-groups', [
            'groups' => $groups,
        ]);
    }

    public function storeProductGroups(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'groups' => ['nullable', 'array'],
            'groups.*' => ['nullable', 'string', 'max:255'],
        ]);

        $rawGroups = collect($data['groups'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        $normalize = static fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $seen = [];
        $hasDuplicates = false;
        $uniqueGroups = $rawGroups->filter(function (string $value) use (&$seen, &$hasDuplicates, $normalize): bool {
            $key = $normalize($value);
            if (isset($seen[$key])) {
                $hasDuplicates = true;
                return false;
            }
            $seen[$key] = true;
            return true;
        })->values();

        if ($hasDuplicates) {
            return redirect()
                ->route('admin.product-groups.index')
                ->withInput(['groups' => $uniqueGroups->all()])
                ->withErrors(['groups' => __('Знайдено дублікати. Кожна група товарів має бути унікальною.')]);
        }

        $groups = $uniqueGroups;

        DB::transaction(function () use ($groups): void {
            ProductGroup::query()->delete();

            foreach ($groups as $index => $name) {
                ProductGroup::create([
                    'name' => $name,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('admin.product-groups.index')
            ->with('status', __('Зміни у групах товарів збережено.'));
    }

    public function productCategories(): View
    {
        $categories = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'material_type', 'code'])
            ->map(fn (ProductCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'material_type' => $category->material_type,
                'code' => $category->code,
            ])
            ->all();

        return view('admin.editgroupsandcategories.product-categories', [
            'categories' => $categories,
            'materialTypeOptions' => [
                'Листовий',
                'Рулонний',
                'Без типу матеріалу',
            ],
        ]);
    }

    public function storeProductCategories(Request $request): RedirectResponse
    {
        $materialTypeOptions = ['Листовий', 'Рулонний', 'Без типу матеріалу'];

        $validator = Validator::make($request->all(), [
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['nullable', 'integer', 'exists:product_categories,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:255'],
            'material_types' => ['nullable', 'array'],
            'material_types.*' => ['nullable', 'string', 'in:Листовий,Рулонний,Без типу матеріалу'],
            'category_codes' => ['nullable', 'array'],
            'category_codes.*' => ['nullable', 'string', 'max:4', 'regex:/^[A-Za-z0-9]+$/'],
        ]);
        $validator->after(function ($validator) use ($request, $materialTypeOptions): void {
            $categories = (array) $request->input('categories', []);
            $materialTypes = (array) $request->input('material_types', []);
            $categoryCodes = (array) $request->input('category_codes', []);

            foreach ($categories as $index => $name) {
                $normalizedName = trim((string) $name);
                if ($normalizedName === '') {
                    continue;
                }

                $materialType = trim((string) ($materialTypes[$index] ?? ''));
                if ($materialType === '' || ! in_array($materialType, $materialTypeOptions, true)) {
                    $validator->errors()->add('categories', __('Оберіть "Тип матеріалу" для кожної категорії товарів.'));
                    break;
                }

                $code = trim((string) ($categoryCodes[$index] ?? ''));
                if ($code === '' || ! preg_match('/^[A-Za-z0-9]{1,4}$/', $code)) {
                    $validator->errors()->add('categories', __('Задайте код . Тільки латинськи символи та(або) цифри!'));
                    break;
                }
            }
        });

        $data = $validator->validate();

        $rawRows = collect($data['categories'] ?? [])
            ->map(function ($name, $index) use ($data) {
                return [
                    'id' => (int) ($data['category_ids'][$index] ?? 0),
                    'name' => trim((string) $name),
                    'material_type' => trim((string) (($data['material_types'][$index] ?? ''))),
                    'code' => strtoupper(trim((string) (($data['category_codes'][$index] ?? '')))),
                ];
            })
            ->filter(fn (array $row) => $row['name'] !== '')
            ->values();

        $normalize = static fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $seen = [];
        $seenCodes = [];
        $hasDuplicates = false;
        $hasDuplicateCodes = false;
        $uniqueRows = $rawRows->filter(function (array $row) use (&$seen, &$seenCodes, &$hasDuplicates, &$hasDuplicateCodes, $normalize): bool {
            $key = $normalize($row['name']);
            if (isset($seen[$key])) {
                $hasDuplicates = true;
                return false;
            }
            $seen[$key] = true;

            $codeKey = strtoupper($row['code']);
            if (isset($seenCodes[$codeKey])) {
                $hasDuplicateCodes = true;
                return false;
            }
            $seenCodes[$codeKey] = true;

            return true;
        })->values();

        if ($hasDuplicates || $hasDuplicateCodes) {
            return redirect()
                ->route('admin.product-categories.index')
                ->withInput([
                    'category_ids' => $uniqueRows->pluck('id')->map(fn ($id) => $id > 0 ? $id : null)->all(),
                    'categories' => $uniqueRows->pluck('name')->all(),
                    'material_types' => $uniqueRows->pluck('material_type')->all(),
                    'category_codes' => $uniqueRows->pluck('code')->all(),
                ])
                ->withErrors(['categories' => $hasDuplicates
                    ? __('Знайдено дублікати. Кожна категорія товарів має бути унікальною.')
                    : __('Знайдено дублікати кодів. Кожен код категорії має бути унікальним.')
                ]);
        }

        $categories = $uniqueRows;

        DB::transaction(function () use ($categories, $normalize): void {
            $existing = ProductCategory::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $existingById = $existing->keyBy('id');
            $existingByName = $existing->keyBy(fn (ProductCategory $category) => $normalize((string) $category->name));
            $existingByCode = $existing->keyBy(fn (ProductCategory $category) => strtoupper((string) $category->code));
            $keptIds = [];

            foreach ($categories as $index => $row) {
                $category = null;

                if (($row['id'] ?? 0) > 0 && $existingById->has($row['id'])) {
                    $category = $existingById->get($row['id']);
                }

                if (!$category) {
                    $category = $existingByName->get($normalize($row['name']))
                        ?? $existingByCode->get(strtoupper($row['code']))
                        ?? new ProductCategory();
                }

                $category->fill([
                    'name' => $row['name'],
                    'material_type' => $row['material_type'],
                    'code' => $row['code'],
                    'sort_order' => $index + 1,
                ]);
                $category->save();
                $keptIds[] = $category->id;
            }

            ProductCategory::query()
                ->whereNotIn('id', $keptIds)
                ->delete();
        });

        return redirect()
            ->route('admin.product-categories.index')
            ->with('status', __('Зміни у категоріях товарів збережено.'));
    }
}
