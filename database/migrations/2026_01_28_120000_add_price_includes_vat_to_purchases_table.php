<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'price_includes_vat')) {
                $table->boolean('price_includes_vat')->default(true)->after('source_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'price_includes_vat')) {
                $table->dropColumn('price_includes_vat');
            }
        });
    }
};
