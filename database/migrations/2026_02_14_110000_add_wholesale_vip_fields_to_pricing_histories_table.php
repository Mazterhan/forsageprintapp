<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_histories', function (Blueprint $table) {
            $table->decimal('markup_wholesale_percent', 6, 2)->nullable()->after('markup_price');
            $table->decimal('wholesale_price', 12, 4)->nullable()->after('markup_wholesale_percent');
            $table->decimal('markup_vip_percent', 6, 2)->nullable()->after('wholesale_price');
            $table->decimal('vip_price', 12, 4)->nullable()->after('markup_vip_percent');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_histories', function (Blueprint $table) {
            $table->dropColumn([
                'markup_wholesale_percent',
                'wholesale_price',
                'markup_vip_percent',
                'vip_price',
            ]);
        });
    }
};
