<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('client_id')->constrained('users')->nullOnDelete();
            $table->index(['created_by', 'updated_at']);
        });

        DB::table('orders')->update([
            'created_by' => DB::raw('last_edited_by'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['created_by', 'updated_at']);
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
