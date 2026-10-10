<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CLIENT_PUBLIC_ID = 'e97b6dd6-9a2d-4cee-b2aa-cef5e404f2e8';

    private const DIRECT_PAYMENT_PUBLIC_ID = '27023889-8c37-46cf-b555-741c6f737e98';

    private const OVERPAYMENT_PAYMENT_PUBLIC_ID = '8b2ce1ff-cc23-4a97-ba6d-7a1d13a27557';

    public function up(): void
    {
        if (! Schema::hasTable('clients') || ! Schema::hasTable('orders') || ! Schema::hasTable('client_payments')) {
            return;
        }

        DB::transaction(function (): void {
            $client = DB::table('clients')
                ->where('public_id', self::CLIENT_PUBLIC_ID)
                ->lockForUpdate()
                ->first(['id']);

            if (! $client) {
                return;
            }

            $payments = DB::table('client_payments')
                ->whereIn('public_id', [
                    self::DIRECT_PAYMENT_PUBLIC_ID,
                    self::OVERPAYMENT_PAYMENT_PUBLIC_ID,
                ])
                ->lockForUpdate()
                ->get()
                ->keyBy('public_id');

            if ($payments->isEmpty()) {
                return;
            }

            if ($payments->count() !== 2) {
                throw new \RuntimeException('Cannot roll back the targeted client payments because only one of the expected payments exists.');
            }

            $directPayment = $payments->get(self::DIRECT_PAYMENT_PUBLIC_ID);
            $overpaymentPayment = $payments->get(self::OVERPAYMENT_PAYMENT_PUBLIC_ID);

            if (
                (int) $directPayment->client_id !== (int) $client->id
                || (int) $overpaymentPayment->client_id !== (int) $client->id
                || ! $directPayment->order_id
                || (int) $directPayment->order_id !== (int) $overpaymentPayment->order_id
                || $directPayment->payment_type !== 'order'
                || (int) $directPayment->is_from_overpayment !== 0
                || (int) $directPayment->is_automatic !== 0
                || (int) $directPayment->amount_uah !== 4430
                || $overpaymentPayment->payment_type !== 'order'
                || (int) $overpaymentPayment->is_from_overpayment !== 1
                || (int) $overpaymentPayment->is_automatic !== 0
                || (int) $overpaymentPayment->amount_uah !== 3030
            ) {
                throw new \RuntimeException('Cannot roll back the targeted client payments because their data no longer matches the approved correction.');
            }

            $order = DB::table('orders')
                ->where('id', $directPayment->order_id)
                ->where('client_id', $client->id)
                ->lockForUpdate()
                ->first(['id', 'total_cost']);

            if (! $order) {
                throw new \RuntimeException('Cannot roll back the targeted client payments because their order is unavailable or belongs to another client.');
            }

            $dependentPaymentsCount = DB::table('client_payments')
                ->whereIn('source_payment_id', [$directPayment->id, $overpaymentPayment->id])
                ->lockForUpdate()
                ->count();

            if ($dependentPaymentsCount > 0) {
                throw new \RuntimeException('Cannot roll back the targeted client payments because they have dependent automatic payments.');
            }

            DB::table('client_payments')
                ->whereIn('id', [$directPayment->id, $overpaymentPayment->id])
                ->delete();

            $paymentsTotal = (int) DB::table('client_payments')
                ->where('order_id', $order->id)
                ->sum('amount_uah');

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'payments_total' => $paymentsTotal,
                    'amount_due' => (float) $order->total_cost - $paymentsTotal,
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        // The deleted payments cannot be recreated safely without their original audit context.
    }
};
