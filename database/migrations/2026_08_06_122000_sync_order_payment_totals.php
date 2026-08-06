<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('client_payments')) {
            return;
        }

        DB::table('orders')
            ->select(['id', 'total_cost'])
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $paymentsTotal = (int) DB::table('client_payments')
                        ->where('order_id', $order->id)
                        ->sum('amount');

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'payments_total' => $paymentsTotal,
                            'amount_due' => (float) $order->total_cost - $paymentsTotal,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Aggregated values cannot be restored reliably to their previous state.
    }
};
