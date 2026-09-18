<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_item_change_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_item_id')->constrained('price_items')->cascadeOnDelete();
            $table->string('field', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['price_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_item_change_histories');
    }
};
