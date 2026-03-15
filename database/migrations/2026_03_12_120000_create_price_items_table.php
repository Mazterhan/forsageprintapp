<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_items', function (Blueprint $table) {
            $table->id();
            $table->string('internal_code')->unique();
            $table->string('name');
            $table->enum('model_type', ['Матеріал', 'Послуга']);
            $table->string('category')->nullable();
            $table->string('material_type')->nullable();
            $table->string('internal_name')->nullable();
            $table->decimal('service_price', 12, 2)->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('measurement_unit')->nullable();
            $table->boolean('for_customer_material')->default(false);
            $table->decimal('width_m', 10, 2)->nullable();
            $table->decimal('length_m', 10, 2)->nullable();
            $table->decimal('thickness_mm', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_items');
    }
};

