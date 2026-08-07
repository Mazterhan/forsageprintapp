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
            $table->boolean('orders_access')->default(false)->after('orders_list_edit');
            $table->string('orders_scope', 10)->default('own')->after('orders_access');
            $table->boolean('orders_update')->default(false)->after('orders_scope');
            $table->boolean('orders_payments')->default(false)->after('orders_update');
            $table->boolean('orders_clients_create')->default(false)->after('orders_clients_manage');
            $table->boolean('orders_clients_edit')->default(false)->after('orders_clients_create');
            $table->boolean('orders_clients_payments')->default(false)->after('orders_clients_edit');
        });

        DB::table('roles')
            ->where('orders_proposals', true)
            ->update([
                'orders_access' => true,
                'orders_scope' => DB::raw('orders_list_scope'),
            ]);

        DB::table('roles')
            ->where('orders_clients_manage', true)
            ->update([
                'orders_clients_create' => true,
                'orders_clients_edit' => true,
                'orders_clients_payments' => true,
                'orders_payments' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn([
                'orders_access',
                'orders_scope',
                'orders_update',
                'orders_payments',
                'orders_clients_create',
                'orders_clients_edit',
                'orders_clients_payments',
            ]);
        });
    }
};
