<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $this->booleanColumn($table, 'analytics_show_kpi', 'can_admin');
            $this->booleanColumn($table, 'analytics_show_charts', 'analytics_show_kpi');
            $this->booleanColumn($table, 'analytics_show_tables', 'analytics_show_charts');
            $this->booleanColumn($table, 'analytics_finance_access', 'analytics_show_tables');
            $this->booleanColumn($table, 'orders_calculation', 'analytics_finance_access');
            $this->booleanColumn($table, 'orders_calc_save', 'orders_calculation');
            $this->booleanColumn($table, 'orders_calc_purchase_visible', 'orders_calc_save');
            $this->booleanColumn($table, 'orders_proposals', 'orders_calc_purchase_visible');
            if (!Schema::hasColumn('roles', 'orders_list_scope')) {
                $table->string('orders_list_scope', 10)->default('own')->after('orders_proposals');
            }
            $this->booleanColumn($table, 'orders_list_purchase_visible', 'orders_list_scope');
            $this->booleanColumn($table, 'orders_edit', 'orders_list_purchase_visible');
            $this->booleanColumn($table, 'orders_list_edit', 'orders_edit');
            $this->booleanColumn($table, 'orders_clients_manage', 'orders_list_edit');
            $this->booleanColumn($table, 'price_create_item', 'orders_clients_manage');
            $this->booleanColumn($table, 'price_deactivate_item', 'price_create_item');
            $this->booleanColumn($table, 'price_delete_item', 'price_deactivate_item');
            $this->booleanColumn($table, 'price_purchase_access', 'price_delete_item');
            $this->booleanColumn($table, 'price_card_access', 'price_purchase_access');
            $this->booleanColumn($table, 'price_card_edit', 'price_card_access');
            $this->booleanColumn($table, 'price_card_history', 'price_card_edit');
            $this->booleanColumn($table, 'admin_reference_manage', 'price_card_history');
            $this->booleanColumn($table, 'admin_users_org_manage', 'admin_reference_manage');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            foreach ([
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
            ] as $column) {
                if (Schema::hasColumn('roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function booleanColumn(Blueprint $table, string $column, string $after): void
    {
        if (!Schema::hasColumn('roles', $column)) {
            $table->boolean($column)->default(false)->after($after);
        }
    }
};
