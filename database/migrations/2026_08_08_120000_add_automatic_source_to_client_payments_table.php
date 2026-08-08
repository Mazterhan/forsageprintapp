<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_payments', function (Blueprint $table): void {
            $table->boolean('is_automatic')->default(false)->after('is_from_overpayment');
            $table->foreignId('source_payment_id')
                ->nullable()
                ->after('is_automatic')
                ->constrained('client_payments')
                ->cascadeOnDelete();
            $table->index(['client_id', 'is_automatic']);
        });
    }

    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table): void {
            $table->dropIndex(['client_id', 'is_automatic']);
            $table->dropConstrainedForeignId('source_payment_id');
            $table->dropColumn('is_automatic');
        });
    }
};
