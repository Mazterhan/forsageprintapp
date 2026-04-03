<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('proposal_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name')->nullable();
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->longText('payload');
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['client_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_proposals');
    }
};
