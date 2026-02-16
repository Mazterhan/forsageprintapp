<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            if (! Schema::hasColumn('pricing_items', 'product_group_id')) {
                $table->foreignId('product_group_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('product_groups')
                    ->nullOnDelete();
            }
        });

        Schema::table('tariffs', function (Blueprint $table) {
            if (! Schema::hasColumn('tariffs', 'product_group_id')) {
                $table->foreignId('product_group_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('product_groups')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_items', 'product_group_id')) {
                $table->dropConstrainedForeignId('product_group_id');
            }
        });

        Schema::table('tariffs', function (Blueprint $table) {
            if (Schema::hasColumn('tariffs', 'product_group_id')) {
                $table->dropConstrainedForeignId('product_group_id');
            }
        });
    }
};
