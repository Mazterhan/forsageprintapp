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
            ->get(['name', 'material_type'])
            ->map(fn (ProductCategory $category) => [
                'name' => $category->name,
                'material_type' => $category->material_type,
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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:255'],
            'material_types' => ['nullable', 'array'],
            'material_types.*' => ['nullable', 'string', 'in:Листовий,Рулонний,Без типу матеріалу'],
        ]);
        $validator->after(function ($validator) use ($request, $materialTypeOptions): void {
            $categories = (array) $request->input('categories', []);
            $materialTypes = (array) $request->input('material_types', []);

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
            }
        });

        $data = $validator->validate();

        $rawRows = collect($data['categories'] ?? [])
            ->map(function ($name, $index) use ($data) {
                return [
                    'name' => trim((string) $name),
                    'material_type' => trim((string) (($data['material_types'][$index] ?? ''))),
                ];
            })
            ->filter(fn (array $row) => $row['name'] !== '')
            ->values();

        $normalize = static fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $seen = [];
        $hasDuplicates = false;
        $uniqueRows = $rawRows->filter(function (array $row) use (&$seen, &$hasDuplicates, $normalize): bool {
            $key = $normalize($row['name']);
            if (isset($seen[$key])) {
                $hasDuplicates = true;
                return false;
            }
            $seen[$key] = true;
            return true;
        })->values();

        if ($hasDuplicates) {
            return redirect()
                ->route('admin.product-categories.index')
                ->withInput([
                    'categories' => $uniqueRows->pluck('name')->all(),
                    'material_types' => $uniqueRows->pluck('material_type')->all(),
                ])
                ->withErrors(['categories' => __('Знайдено дублікати. Кожна категорія товарів має бути унікальною.')]);
        }

        $categories = $uniqueRows;

        DB::transaction(function () use ($categories): void {
            ProductCategory::query()->delete();

            foreach ($categories as $index => $row) {
                ProductCategory::create([
                    'name' => $row['name'],
                    'material_type' => $row['material_type'],
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('admin.product-categories.index')
            ->with('status', __('Зміни у категоріях товарів збережено.'));
    }
}
