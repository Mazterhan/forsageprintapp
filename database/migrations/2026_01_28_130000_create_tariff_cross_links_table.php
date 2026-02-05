<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_cross_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_tariff_id')->constrained('tariffs')->cascadeOnDelete();
            $table->string('child_internal_code')->unique();
            $table->foreignId('child_supplier_id')->constrained('suppliers');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['parent_tariff_id', 'child_internal_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_cross_links');
    }
};
