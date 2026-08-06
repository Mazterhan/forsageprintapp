<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('UAH');
            $table->string('payment_type', 20);
            $table->dateTime('paid_at');
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'paid_at']);
            $table->index(['order_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
