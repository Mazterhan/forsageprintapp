<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('price_items')
            ->select(['id', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                $itemIdsWithCreationHistory = DB::table('price_item_change_histories')
                    ->whereIn('price_item_id', $items->pluck('id'))
                    ->where('field', 'created')
                    ->pluck('price_item_id')
                    ->all();

                $existingIds = array_fill_keys(array_map('intval', $itemIdsWithCreationHistory), true);
                $rows = [];

                foreach ($items as $item) {
                    if (isset($existingIds[(int) $item->id])) {
                        continue;
                    }

                    $rows[] = [
                        'price_item_id' => $item->id,
                        'field' => 'created',
                        'old_value' => null,
                        'new_value' => null,
                        'user_id' => null,
                        'created_at' => $item->created_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('price_item_change_histories')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // Історичні записи не видаляються під час відкочування data-міграції.
    }
};
