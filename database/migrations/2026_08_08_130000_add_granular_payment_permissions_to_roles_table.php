<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('orders_payments_overpayment')->default(false)->after('orders_payments');
            $table->boolean('orders_payments_edit')->default(false)->after('orders_payments_overpayment');
            $table->boolean('orders_clients_overpayments_manage')->default(false)->after('orders_clients_payments');
            $table->boolean('orders_clients_payments_edit')->default(false)->after('orders_clients_overpayments_manage');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn([
                'orders_payments_overpayment',
                'orders_payments_edit',
                'orders_clients_overpayments_manage',
                'orders_clients_payments_edit',
            ]);
        });
    }
};
