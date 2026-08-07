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
            $table->boolean('analytics_orders_access')->default(false)->after('analytics_finance_access');
        });

        // Preserve access to the already existing tab for current analytics roles.
        DB::table('roles')
            ->where('can_analytics', true)
            ->update(['analytics_orders_access' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('analytics_orders_access');
        });
    }
};
