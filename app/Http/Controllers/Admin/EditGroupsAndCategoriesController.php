<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->pluck('name')
            ->all();

        return view('admin.editgroupsandcategories.product-categories', [
            'categories' => $categories,
        ]);
    }

    public function storeProductCategories(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:255'],
        ]);

        $rawCategories = collect($data['categories'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        $normalize = static fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $seen = [];
        $hasDuplicates = false;
        $uniqueCategories = $rawCategories->filter(function (string $value) use (&$seen, &$hasDuplicates, $normalize): bool {
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
                ->route('admin.product-categories.index')
                ->withInput(['categories' => $uniqueCategories->all()])
                ->withErrors(['categories' => __('Знайдено дублікати. Кожна категорія товарів має бути унікальною.')]);
        }

        $categories = $uniqueCategories;

        DB::transaction(function () use ($categories): void {
            ProductCategory::query()->delete();

            foreach ($categories as $index => $name) {
                ProductCategory::create([
                    'name' => $name,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('admin.product-categories.index')
            ->with('status', __('Зміни у категоріях товарів збережено.'));
    }
}
