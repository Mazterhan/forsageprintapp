<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_proposal_edit_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_proposal_id')->constrained('order_proposals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lock_token', 80);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamps();

            $table->unique('order_proposal_id');
            $table->index(['user_id', 'heartbeat_at']);
            $table->index('lock_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_proposal_edit_locks');
    }
};
