<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\DepartmentPosition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->withCount(['categories', 'positions'])
            ->orderBy('name')
            ->get();

        return view('admin.departments.index', [
            'departments' => $departments,
        ]);
    }

    public function create(): View
    {
        $activeUsers = User::query()
            ->where('is_active', true)
            ->with(['department', 'position'])
            ->orderBy('name')
            ->get();

        return view('admin.departments.create', [
            'department' => new Department(),
            'categories' => collect(),
            'positions' => collect(),
            'lockedPositions' => [],
            'activeUsers' => $activeUsers,
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $department = Department::create([
            'name' => $data['name'],
            'lead_user_id' => $data['lead_user_id'] ?? null,
        ]);

        $categories = $this->normalizeList($data['categories'] ?? []);
        $positions = $this->normalizePositions($data['positions_name'] ?? [], $data['positions_category_id'] ?? []);

        foreach ($categories as $name) {
            DepartmentCategory::create([
                'department_id' => $department->id,
                'name' => $name,
            ]);
        }

        foreach ($positions as $position) {
            DepartmentPosition::create([
                'department_id' => $department->id,
                'name' => $position['name'],
                'department_category_id' => $position['category_id'],
            ]);
        }

        return redirect()
            ->route('admin.departments.edit', $department)
            ->with('status', __('Department created.'));
    }

    public function edit(Department $department): View
    {
        $department->load(['categories', 'positions']);

        $activeUsers = User::query()
            ->where('is_active', true)
            ->with(['department', 'position'])
            ->orderBy('name')
            ->get();

        $lockedPositions = DepartmentPosition::query()
            ->where('department_id', $department->id)
            ->whereIn('id', User::query()->whereNotNull('department_position_id')->pluck('department_position_id'))
            ->pluck('id')
            ->all();

        return view('admin.departments.edit', [
            'department' => $department,
            'categories' => $department->categories,
            'positions' => $department->positions,
            'lockedPositions' => $lockedPositions,
            'activeUsers' => $activeUsers,
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();

        $department->update([
            'name' => $data['name'],
            'lead_user_id' => $data['lead_user_id'] ?? null,
        ]);

        $warnings = [];
        $categoryNames = $this->normalizeList($data['categories'] ?? []);
        $positions = $this->normalizePositions($data['positions_name'] ?? [], $data['positions_category_id'] ?? []);

        $existingCategories = $department->categories()->pluck('name', 'id');
        $existingPositions = $department->positions()->get();

        $existingCategoryNames = $existingCategories->values()->all();
        $existingPositionNames = $existingPositions->pluck('name')->all();

        $categoriesToDelete = $existingCategories->filter(fn ($name) => ! in_array($name, $categoryNames, true))->keys()->all();
        if (! empty($categoriesToDelete)) {
            DepartmentCategory::query()
                ->where('department_id', $department->id)
                ->whereIn('id', $categoriesToDelete)
                ->delete();
        }

        foreach ($categoryNames as $name) {
            if (! in_array($name, $existingCategoryNames, true)) {
                DepartmentCategory::create([
                    'department_id' => $department->id,
                    'name' => $name,
                ]);
            }
        }

        $incomingPositionNames = array_column($positions, 'name');
        $positionsToDelete = $existingPositions->filter(fn ($position) => ! in_array($position->name, $incomingPositionNames, true));
        foreach ($positionsToDelete as $position) {
            $isAssigned = User::query()
                ->where('department_position_id', $position->id)
                ->exists();

            if ($isAssigned) {
                $warnings[] = __('Position ":name" is assigned to users and cannot be deleted.', ['name' => $position->name]);
                continue;
            }

            DepartmentPosition::query()
                ->where('department_id', $department->id)
                ->where('id', $position->id)
                ->delete();
        }

        foreach ($positions as $position) {
            if (! in_array($position['name'], $existingPositionNames, true)) {
                DepartmentPosition::create([
                    'department_id' => $department->id,
                    'name' => $position['name'],
                    'department_category_id' => $position['category_id'],
                ]);
            } else {
                DepartmentPosition::query()
                    ->where('department_id', $department->id)
                    ->where('name', $position['name'])
                    ->update(['department_category_id' => $position['category_id']]);
            }
        }

        $redirect = redirect()
            ->route('admin.departments.edit', $department)
            ->with('status', __('Department updated.'));

        if (! empty($warnings)) {
            $redirect->with('warnings', $warnings);
        }

        return $redirect;
    }

    private function normalizeList(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value === '') {
                continue;
            }
            $normalized[] = Str::squish($value);
        }

        return array_values(array_unique($normalized));
    }

    private function normalizePositions(array $names, array $categoryIds): array
    {
        $normalized = [];

        foreach ($names as $index => $name) {
            $value = trim((string) $name);
            if ($value === '') {
                continue;
            }
            $categoryId = $categoryIds[$index] ?? null;
            $categoryId = $categoryId !== '' ? (int) $categoryId : null;
            $normalized[$value] = [
                'name' => Str::squish($value),
                'category_id' => $categoryId ?: null,
            ];
        }

        return array_values($normalized);
    }
}
