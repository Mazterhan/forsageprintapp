<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'order_proposals',
        'clients',
        'users',
        'roles',
        'departments',
        'price_items',
        'price_item_histories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasColumn($tableName, 'public_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->uuid('public_id')->nullable()->after('id');
                });
            }

            $this->backfillPublicIds($tableName);

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unique('public_id', $this->indexName($tableName));
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (!Schema::hasColumn($tableName, 'public_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropUnique($this->indexName($tableName));
                $table->dropColumn('public_id');
            });
        }
    }

    private function backfillPublicIds(string $tableName): void
    {
        DB::table($tableName)
            ->whereNull('public_id')
            ->orderBy('id')
            ->select('id')
            ->chunkById(200, function ($rows) use ($tableName): void {
                foreach ($rows as $row) {
                    DB::table($tableName)
                        ->where('id', $row->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });
    }

    private function indexName(string $tableName): string
    {
        return $tableName.'_public_id_unique';
    }
};
