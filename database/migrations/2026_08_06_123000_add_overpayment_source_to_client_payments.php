<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_payments', function (Blueprint $table): void {
            $table->boolean('is_from_overpayment')->default(false)->after('payment_type');
            $table->index(['client_id', 'is_from_overpayment']);
        });
    }

    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table): void {
            $table->dropIndex(['client_id', 'is_from_overpayment']);
            $table->dropColumn('is_from_overpayment');
        });
    }
};
