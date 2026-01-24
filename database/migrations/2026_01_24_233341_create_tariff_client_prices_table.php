<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_client_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')->constrained('tariffs')->cascadeOnDelete();
            $table->string('client_category');
            $table->decimal('price', 12, 2);
            $table->timestamps();

            $table->unique(['tariff_id', 'client_category']);
            $table->index('client_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_client_prices');
    }
};
