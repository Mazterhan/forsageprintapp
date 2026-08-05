<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('customer_name')->constrained('clients')->nullOnDelete();
            $table->json('items')->nullable()->after('last_edited_by');
            $table->decimal('payments_total', 14, 2)->default(0)->after('items');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['items', 'payments_total']);
        });
    }
};
