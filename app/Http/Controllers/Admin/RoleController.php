<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function create(): View
    {
        return view('admin.roles.create');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.create', [
            'role' => $role,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $booleanFields = [
            'can_analytics',
            'analytics_show_kpi',
            'analytics_show_charts',
            'analytics_show_tables',
            'analytics_finance_access',
            'can_orders',
            'orders_calculation',
            'orders_calc_save',
            'orders_calc_purchase_visible',
            'orders_proposals',
            'orders_list_purchase_visible',
            'orders_edit',
            'orders_list_edit',
            'orders_clients_manage',
            'can_price',
            'price_create_item',
            'price_deactivate_item',
            'price_delete_item',
            'price_purchase_access',
            'price_card_access',
            'price_card_edit',
            'price_card_history',
            'can_admin',
            'admin_reference_manage',
            'admin_users_org_manage',
        ];

        $data = $this->validatedRoleData($request, $booleanFields);

        $data = $this->normalizePermissions($data, $booleanFields);

        $slugBase = Str::slug($data['name']);
        $slug = $slugBase !== '' ? $slugBase : Str::lower(Str::random(8));
        $counter = 1;
        while (Role::query()->where('slug', $slug)->exists()) {
            $slug = ($slugBase !== '' ? $slugBase : 'role').'-'.$counter;
            $counter++;
        }

        Role::query()->create(array_merge($data, [
            'slug' => $slug,
        ]));

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Роль додано.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $booleanFields = $this->booleanFields();
        $data = $this->validatedRoleData($request, $booleanFields, $role);
        $data = $this->normalizePermissions($data, $booleanFields);

        $role->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Роль оновлено.');
    }

    private function booleanFields(): array
    {
        return [
            'can_analytics',
            'analytics_show_kpi',
            'analytics_show_charts',
            'analytics_show_tables',
            'analytics_finance_access',
            'can_orders',
            'orders_calculation',
            'orders_calc_save',
            'orders_calc_purchase_visible',
            'orders_proposals',
            'orders_list_purchase_visible',
            'orders_edit',
            'orders_list_edit',
            'orders_clients_manage',
            'can_price',
            'price_create_item',
            'price_deactivate_item',
            'price_delete_item',
            'price_purchase_access',
            'price_card_access',
            'price_card_edit',
            'price_card_history',
            'can_admin',
            'admin_reference_manage',
            'admin_users_org_manage',
        ];
    }

    private function validatedRoleData(Request $request, array $booleanFields, ?Role $role = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role), function (string $attribute, mixed $value, \Closure $fail): void {
                if (Str::lower(trim((string) $value)) === 'admin') {
                    $fail('Назва ролі Admin зарезервована.');
                }
            }],
            'orders_list_scope' => ['nullable', 'in:own,all'],
        ];

        foreach ($booleanFields as $field) {
            $rules[$field] = ['required', 'boolean'];
        }

        return $request->validate([
            ...$rules,
        ]);
    }

    private function normalizePermissions(array $data, array $booleanFields): array
    {
        foreach ($booleanFields as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        if (!$data['can_analytics']) {
            foreach (['analytics_show_kpi', 'analytics_show_charts', 'analytics_show_tables', 'analytics_finance_access'] as $field) {
                $data[$field] = false;
            }
        }

        if (!$data['can_orders']) {
            foreach ([
                'orders_calculation',
                'orders_calc_save',
                'orders_calc_purchase_visible',
                'orders_proposals',
                'orders_list_purchase_visible',
                'orders_edit',
                'orders_list_edit',
                'orders_clients_manage',
            ] as $field) {
                $data[$field] = false;
            }
        }

        if (!$data['orders_calculation']) {
            $data['orders_calc_save'] = false;
            $data['orders_calc_purchase_visible'] = false;
        }

        if (!$data['orders_proposals']) {
            $data['orders_list_scope'] = 'own';
            $data['orders_list_purchase_visible'] = false;
            $data['orders_edit'] = false;
            $data['orders_list_edit'] = false;
        }

        if (!$data['can_price']) {
            foreach ([
                'price_create_item',
                'price_deactivate_item',
                'price_delete_item',
                'price_purchase_access',
                'price_card_access',
                'price_card_edit',
                'price_card_history',
            ] as $field) {
                $data[$field] = false;
            }
        }

        if (!$data['price_card_access']) {
            $data['price_card_edit'] = false;
            $data['price_card_history'] = false;
            $data['price_deactivate_item'] = false;
            $data['price_delete_item'] = false;
        }

        if (!$data['can_admin']) {
            $data['admin_reference_manage'] = false;
            $data['admin_users_org_manage'] = false;
        }

        $data['orders_list_scope'] = ($data['orders_list_scope'] ?? 'own') === 'all' ? 'all' : 'own';

        return $data;
    }
}
