<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;

class PermissionService
{
    private const PERMISSION_COLUMNS = [
        'analytics' => 'can_analytics',
        'analytics_show_kpi' => 'analytics_show_kpi',
        'analytics_show_charts' => 'analytics_show_charts',
        'analytics_show_tables' => 'analytics_show_tables',
        'analytics_finance_access' => 'analytics_finance_access',
        'orders' => 'can_orders',
        'orders_calculation' => 'orders_calculation',
        'orders_calc_save' => 'orders_calc_save',
        'orders_calc_purchase_visible' => 'orders_calc_purchase_visible',
        'orders_proposals' => 'orders_proposals',
        'orders_list_purchase_visible' => 'orders_list_purchase_visible',
        'orders_edit' => 'orders_edit',
        'orders_list_edit' => 'orders_list_edit',
        'orders_clients_manage' => 'orders_clients_manage',
        'price' => 'can_price',
        'price_create_item' => 'price_create_item',
        'price_deactivate_item' => 'price_deactivate_item',
        'price_delete_item' => 'price_delete_item',
        'price_purchase_access' => 'price_purchase_access',
        'price_card_access' => 'price_card_access',
        'price_card_edit' => 'price_card_edit',
        'price_card_history' => 'price_card_history',
        'admin' => 'can_admin',
        'admin_reference_manage' => 'admin_reference_manage',
        'admin_users_org_manage' => 'admin_users_org_manage',
    ];

    private const DEPENDENCIES = [
        'analytics_show_kpi' => ['analytics'],
        'analytics_show_charts' => ['analytics'],
        'analytics_show_tables' => ['analytics'],
        'analytics_finance_access' => ['analytics'],
        'orders_calculation' => ['orders'],
        'orders_calc_save' => ['orders_calculation'],
        'orders_calc_purchase_visible' => ['orders_calculation'],
        'orders_proposals' => ['orders'],
        'orders_list_purchase_visible' => ['orders_proposals'],
        'orders_edit' => ['orders_proposals'],
        'orders_list_edit' => ['orders_proposals'],
        'orders_clients_manage' => ['orders'],
        'price_create_item' => ['price'],
        'price_deactivate_item' => ['price'],
        'price_delete_item' => ['price'],
        'price_purchase_access' => ['price'],
        'price_card_access' => ['price'],
        'price_card_edit' => ['price_card_access'],
        'price_card_history' => ['price_card_access'],
        'admin_reference_manage' => ['admin'],
        'admin_users_org_manage' => ['admin'],
    ];

    /** @var array<string, Role|null> */
    private array $roleCache = [];

    public function isAdmin(?User $user): bool
    {
        return (string) ($user?->role ?? '') === 'admin';
    }

    public function can(?User $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if (!array_key_exists($permission, self::PERMISSION_COLUMNS)) {
            return false;
        }

        foreach (self::DEPENDENCIES[$permission] ?? [] as $dependency) {
            if (!$this->can($user, $dependency)) {
                return false;
            }
        }

        $role = $this->roleFor($user);
        if (!$role) {
            return false;
        }

        return (bool) ($role->{self::PERMISSION_COLUMNS[$permission]} ?? false);
    }

    public function ordersListScope(?User $user): string
    {
        if (!$user) {
            return 'own';
        }

        if ($this->isAdmin($user)) {
            return 'all';
        }

        $role = $this->roleFor($user);
        if (!$role || !$this->can($user, 'orders_proposals')) {
            return 'own';
        }

        return (string) ($role->orders_list_scope ?? 'own') === 'all' ? 'all' : 'own';
    }

    private function roleFor(User $user): ?Role
    {
        $slug = (string) ($user->role ?? '');
        if ($slug === '' || $slug === 'admin') {
            return null;
        }

        if (!array_key_exists($slug, $this->roleCache)) {
            $this->roleCache[$slug] = Role::query()->where('slug', $slug)->first();
        }

        return $this->roleCache[$slug];
    }
}
