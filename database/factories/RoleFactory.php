<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'can_analytics' => false,
            'analytics_show_kpi' => false,
            'analytics_show_charts' => false,
            'analytics_show_tables' => false,
            'analytics_finance_access' => false,
            'analytics_orders_access' => false,
            'analytics_orders_show_kpi' => false,
            'analytics_orders_show_charts' => false,
            'analytics_orders_show_tables' => false,
            'analytics_orders_finance_access' => false,
            'can_orders' => false,
            'orders_calculation' => false,
            'orders_calc_save' => false,
            'orders_calc_purchase_visible' => false,
            'orders_proposals' => false,
            'orders_list_scope' => 'own',
            'orders_list_purchase_visible' => false,
            'orders_edit' => false,
            'orders_list_edit' => false,
            'orders_access' => false,
            'orders_scope' => 'own',
            'orders_update' => false,
            'orders_payments' => false,
            'orders_payments_overpayment' => false,
            'orders_payments_edit' => false,
            'orders_clients_manage' => false,
            'orders_clients_create' => false,
            'orders_clients_edit' => false,
            'orders_clients_payments' => false,
            'orders_clients_overpayments_manage' => false,
            'orders_clients_payments_edit' => false,
            'can_price' => false,
            'price_create_item' => false,
            'price_deactivate_item' => false,
            'price_delete_item' => false,
            'price_purchase_access' => false,
            'price_card_access' => false,
            'price_card_edit' => false,
            'price_card_history' => false,
            'can_admin' => false,
            'admin_reference_manage' => false,
            'admin_users_org_manage' => false,
        ];
    }

    public function withPermissions(array $permissions): static
    {
        if (($permissions['can_orders'] ?? false) && ! array_key_exists('orders_access', $permissions)) {
            $permissions['orders_proposals'] ??= true;
            $permissions['orders_access'] = true;
            $permissions['orders_scope'] = $permissions['orders_scope'] ?? 'all';
            $permissions['orders_update'] = true;
            $permissions['orders_payments'] = true;
            $permissions['orders_payments_overpayment'] = true;
            $permissions['orders_payments_edit'] = true;
        }

        if (($permissions['orders_clients_manage'] ?? false)) {
            $permissions['orders_clients_create'] ??= true;
            $permissions['orders_clients_edit'] ??= true;
            $permissions['orders_clients_payments'] ??= true;
            $permissions['orders_clients_overpayments_manage'] ??= true;
            $permissions['orders_clients_payments_edit'] ??= true;
        }

        return $this->state(fn () => $permissions);
    }

    public function fullAccess(): static
    {
        return $this->state(fn () => [
            'can_analytics' => true,
            'analytics_show_kpi' => true,
            'analytics_show_charts' => true,
            'analytics_show_tables' => true,
            'analytics_finance_access' => true,
            'analytics_orders_access' => true,
            'analytics_orders_show_kpi' => true,
            'analytics_orders_show_charts' => true,
            'analytics_orders_show_tables' => true,
            'analytics_orders_finance_access' => true,
            'can_orders' => true,
            'orders_calculation' => true,
            'orders_calc_save' => true,
            'orders_calc_purchase_visible' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'all',
            'orders_list_purchase_visible' => true,
            'orders_edit' => true,
            'orders_list_edit' => true,
            'orders_access' => true,
            'orders_scope' => 'all',
            'orders_update' => true,
            'orders_payments' => true,
            'orders_payments_overpayment' => true,
            'orders_payments_edit' => true,
            'orders_clients_manage' => true,
            'orders_clients_create' => true,
            'orders_clients_edit' => true,
            'orders_clients_payments' => true,
            'orders_clients_overpayments_manage' => true,
            'orders_clients_payments_edit' => true,
            'can_price' => true,
            'price_create_item' => true,
            'price_deactivate_item' => true,
            'price_delete_item' => true,
            'price_purchase_access' => true,
            'price_card_access' => true,
            'price_card_edit' => true,
            'price_card_history' => true,
            'can_admin' => true,
            'admin_reference_manage' => true,
            'admin_users_org_manage' => true,
        ]);
    }
}
