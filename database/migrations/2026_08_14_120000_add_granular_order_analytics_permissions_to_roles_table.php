<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('analytics_orders_show_kpi')->default(false)->after('analytics_orders_access');
            $table->boolean('analytics_orders_show_charts')->default(false)->after('analytics_orders_show_kpi');
            $table->boolean('analytics_orders_show_tables')->default(false)->after('analytics_orders_show_charts');
            $table->boolean('analytics_orders_finance_access')->default(false)->after('analytics_orders_show_tables');
        });

        // Roles which could already open the orders tab keep the same visible blocks.
        DB::table('roles')
            ->where('can_analytics', true)
            ->where('analytics_orders_access', true)
            ->update([
                'analytics_orders_show_kpi' => DB::raw('analytics_show_kpi'),
                'analytics_orders_show_charts' => DB::raw('analytics_show_charts'),
                'analytics_orders_show_tables' => DB::raw('analytics_show_tables'),
                'analytics_orders_finance_access' => DB::raw('analytics_finance_access'),
            ]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn([
                'analytics_orders_show_kpi',
                'analytics_orders_show_charts',
                'analytics_orders_show_tables',
                'analytics_orders_finance_access',
            ]);
        });
    }
};
