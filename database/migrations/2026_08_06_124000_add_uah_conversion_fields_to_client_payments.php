<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('amount_uah')->default(0)->after('amount');
            $table->unsignedBigInteger('calculated_amount_uah')->nullable()->after('amount_uah');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('currency');
            $table->string('exchange_rate_type', 10)->nullable()->after('exchange_rate');
            $table->string('exchange_rate_source', 50)->nullable()->after('exchange_rate_type');
            $table->dateTime('exchange_rate_fetched_at')->nullable()->after('exchange_rate_source');
        });

        DB::table('client_payments')->update([
            'amount_uah' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'amount_uah',
                'calculated_amount_uah',
                'exchange_rate',
                'exchange_rate_type',
                'exchange_rate_source',
                'exchange_rate_fetched_at',
            ]);
        });
    }
};
