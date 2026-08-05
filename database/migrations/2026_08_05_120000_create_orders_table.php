<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('order_number')->nullable()->unique();
            $table->string('customer_name')->nullable();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount_due', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->timestamps();

            $table->index('updated_at');
            $table->index('customer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
