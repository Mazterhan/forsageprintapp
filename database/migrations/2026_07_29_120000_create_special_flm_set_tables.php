<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('special_flm_set_items')) {
            Schema::create('special_flm_set_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('price_item_id')->constrained('price_items')->cascadeOnDelete();
                $table->string('internal_code');
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('price_item_id');
                $table->index('internal_code');
            });
        }

        if (! Schema::hasTable('special_flm_set_histories')) {
            Schema::create('special_flm_set_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50);
                $table->string('internal_code')->nullable();
                $table->string('name')->nullable();
                $table->text('change_summary');
                $table->timestamps();

                $table->index('internal_code');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('special_flm_set_histories');
        Schema::dropIfExists('special_flm_set_items');
    }
};
