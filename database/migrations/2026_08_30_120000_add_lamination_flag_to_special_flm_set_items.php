<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('special_flm_set_items')) {
            return;
        }

        if (! Schema::hasColumn('special_flm_set_items', 'has_lamination')) {
            Schema::table('special_flm_set_items', function (Blueprint $table): void {
                $table->boolean('has_lamination')->default(false)->after('name');
            });
        }

        DB::table('special_flm_set_items')
            ->whereIn('internal_code', ['MAT-FLM-011', 'MAT-FLM-015'])
            ->update(['has_lamination' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('special_flm_set_items') || ! Schema::hasColumn('special_flm_set_items', 'has_lamination')) {
            return;
        }

        Schema::table('special_flm_set_items', function (Blueprint $table): void {
            $table->dropColumn('has_lamination');
        });
    }
};
