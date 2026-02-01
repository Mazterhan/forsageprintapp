<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('id')
            ->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $departments = Department::query()
            ->with(['categories', 'positions'])
            ->orderBy('name')
            ->get();

        return view('admin.users.create', [
            'departments' => $departments,
        ]);
    }

    public function edit(User $user): View
    {
        $departments = Department::query()
            ->with(['categories', 'positions'])
            ->orderBy('name')
            ->get();

        return view('admin.users.edit', [
            'user' => $user,
            'departments' => $departments,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'department_id' => $data['department_id'] ?? null,
            'department_category_id' => $data['department_category_id'] ?? null,
            'department_position_id' => $data['department_position_id'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('User created.'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->id === auth()->id() && ! $data['is_active']) {
            return redirect()
                ->route('admin.users.edit', $user)
                ->withErrors(['is_active' => __('You cannot deactivate your own account.')]);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'department_id' => $data['department_id'] ?? null,
            'department_category_id' => $data['department_category_id'] ?? null,
            'department_position_id' => $data['department_position_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('User updated.'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['toggle' => __('You cannot change your own status.')]);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('User status updated.'));
    }
}
