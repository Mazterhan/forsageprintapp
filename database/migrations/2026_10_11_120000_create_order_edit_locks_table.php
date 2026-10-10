<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_edit_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('lock_token', 80);
            $table->timestamp('heartbeat_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_edit_locks');
    }
};
