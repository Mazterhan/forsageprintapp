<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_proposals', function (Blueprint $table): void {
            $table->boolean('is_autosaved')->default(false)->after('deleted_date');
            $table->foreignId('autosaved_by')->nullable()->after('is_autosaved')->constrained('users')->nullOnDelete();
            $table->timestamp('autosaved_at')->nullable()->after('autosaved_by');
            $table->string('autosave_token', 80)->nullable()->after('autosaved_at');
            $table->foreignId('autosave_confirmed_by')->nullable()->after('autosave_token')->constrained('users')->nullOnDelete();
            $table->timestamp('autosave_confirmed_at')->nullable()->after('autosave_confirmed_by');

            $table->index(['is_autosaved', 'autosaved_by']);
            $table->index('autosaved_at');
            $table->index('autosave_token');
        });
    }

    public function down(): void
    {
        Schema::table('order_proposals', function (Blueprint $table): void {
            $table->dropForeign(['autosaved_by']);
            $table->dropForeign(['autosave_confirmed_by']);
            $table->dropIndex(['is_autosaved', 'autosaved_by']);
            $table->dropIndex(['autosaved_at']);
            $table->dropIndex(['autosave_token']);
            $table->dropColumn([
                'is_autosaved',
                'autosaved_by',
                'autosaved_at',
                'autosave_token',
                'autosave_confirmed_by',
                'autosave_confirmed_at',
            ]);
        });
    }
};
