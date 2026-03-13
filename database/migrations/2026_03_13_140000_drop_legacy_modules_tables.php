<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'purchase_import_errors',
            'supplier_documents',
            'purchase_items',
            'purchases',
            'suppliers',
            'pricing_histories',
            'pricing_items',
            'subcontractors',
            'tariff_client_prices',
            'tariff_cross_links',
            'tariffs',
            'product_groups',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Legacy module tables were removed intentionally.
        // Recreating them is out of scope for this rollback.
    }
};
