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
            'can_orders' => false,
            'orders_calculation' => false,
            'orders_calc_save' => false,
            'orders_calc_purchase_visible' => false,
            'orders_proposals' => false,
            'orders_list_scope' => 'own',
            'orders_list_purchase_visible' => false,
            'orders_edit' => false,
            'orders_list_edit' => false,
            'orders_clients_manage' => false,
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
            'can_orders' => true,
            'orders_calculation' => true,
            'orders_calc_save' => true,
            'orders_calc_purchase_visible' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'all',
            'orders_list_purchase_visible' => true,
            'orders_edit' => true,
            'orders_list_edit' => true,
            'orders_clients_manage' => true,
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
