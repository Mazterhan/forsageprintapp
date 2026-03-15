<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_item_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_item_id')->constrained('price_items')->cascadeOnDelete();
            $table->decimal('service_price', 12, 2);
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('markup_percent', 8, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['price_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_item_histories');
    }
};

