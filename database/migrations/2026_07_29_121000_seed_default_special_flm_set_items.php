<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('special_flm_set_items') || ! Schema::hasTable('special_flm_set_histories')) {
            return;
        }

        $hasItems = DB::table('special_flm_set_items')->exists();
        $hasHistory = DB::table('special_flm_set_histories')->exists();

        if ($hasItems || $hasHistory) {
            return;
        }

        $now = now();
        $items = DB::table('price_items')
            ->where('model_type', 'Матеріал')
            ->whereIn('internal_code', ['MAT-FLM-010', 'MAT-FLM-011'])
            ->get(['id', 'internal_code', 'name'])
            ->sortBy(fn ($item) => array_search($item->internal_code, ['MAT-FLM-010', 'MAT-FLM-011'], true))
            ->values();

        foreach ($items as $index => $item) {
            DB::table('special_flm_set_items')->insert([
                'price_item_id' => $item->id,
                'internal_code' => $item->internal_code,
                'name' => $item->name,
                'sort_order' => $index,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('special_flm_set_histories')->insert([
                'user_id' => null,
                'action' => 'seeded',
                'internal_code' => $item->internal_code,
                'name' => $item->name,
                'change_summary' => 'Позицію додано до початкового списку спеціальних FLM-позицій.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
