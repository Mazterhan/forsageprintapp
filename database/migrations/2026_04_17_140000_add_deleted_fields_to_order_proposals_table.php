<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_proposals', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_date')->nullable()->after('updated_at');
            $table->index('deleted_date');
        });
    }

    public function down(): void
    {
        Schema::table('order_proposals', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropIndex(['deleted_date']);
            $table->dropColumn(['deleted_by', 'deleted_date']);
        });
    }
};
