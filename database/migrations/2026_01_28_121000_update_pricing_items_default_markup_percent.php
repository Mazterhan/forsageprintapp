<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pricing_items MODIFY markup_percent DECIMAL(6,2) NOT NULL DEFAULT 50');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pricing_items MODIFY markup_percent DECIMAL(6,2) NOT NULL DEFAULT 30');
    }
};
