<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('external_code')->nullable();
            $table->string('internal_code');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('qty', 12, 3)->nullable();
            $table->decimal('price_raw', 12, 4)->nullable();
            $table->decimal('price_vat', 12, 4);
            $table->string('row_hash', 64)->index();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'internal_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
