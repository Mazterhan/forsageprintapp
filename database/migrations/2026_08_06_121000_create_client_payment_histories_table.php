<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payment_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_payment_id')->constrained('client_payments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('changes');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_payment_id', 'created_at'], 'client_payment_history_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payment_histories');
    }
};
