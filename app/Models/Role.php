<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'can_analytics',
        'can_orders',
        'can_price',
        'can_admin',
        'analytics_show_kpi',
        'analytics_show_charts',
        'analytics_show_tables',
        'analytics_finance_access',
        'orders_calculation',
        'orders_calc_save',
        'orders_calc_purchase_visible',
        'orders_proposals',
        'orders_list_scope',
        'orders_list_purchase_visible',
        'orders_edit',
        'orders_list_edit',
        'orders_clients_manage',
        'price_create_item',
        'price_deactivate_item',
        'price_delete_item',
        'price_purchase_access',
        'price_card_access',
        'price_card_edit',
        'price_card_history',
        'admin_reference_manage',
        'admin_users_org_manage',
    ];

    protected $casts = [
        'can_analytics' => 'boolean',
        'can_orders' => 'boolean',
        'can_price' => 'boolean',
        'can_admin' => 'boolean',
        'analytics_show_kpi' => 'boolean',
        'analytics_show_charts' => 'boolean',
        'analytics_show_tables' => 'boolean',
        'analytics_finance_access' => 'boolean',
        'orders_calculation' => 'boolean',
        'orders_calc_save' => 'boolean',
        'orders_calc_purchase_visible' => 'boolean',
        'orders_proposals' => 'boolean',
        'orders_list_purchase_visible' => 'boolean',
        'orders_edit' => 'boolean',
        'orders_list_edit' => 'boolean',
        'orders_clients_manage' => 'boolean',
        'price_create_item' => 'boolean',
        'price_deactivate_item' => 'boolean',
        'price_delete_item' => 'boolean',
        'price_purchase_access' => 'boolean',
        'price_card_access' => 'boolean',
        'price_card_edit' => 'boolean',
        'price_card_history' => 'boolean',
        'admin_reference_manage' => 'boolean',
        'admin_users_org_manage' => 'boolean',
    ];
}
