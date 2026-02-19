<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_type_category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->foreignId('product_type_id')->constrained('product_types')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['product_category_id', 'product_type_id'], 'ptcr_unique_category_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_type_category_rules');
    }
};

