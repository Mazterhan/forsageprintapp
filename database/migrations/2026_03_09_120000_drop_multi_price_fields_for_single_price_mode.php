<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tariff_client_prices')) {
            Schema::drop('tariff_client_prices');
        }

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'price_type')) {
                $table->dropColumn('price_type');
            }
        });

        Schema::table('tariffs', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('tariffs', 'wholesale_price')) {
                $columns[] = 'wholesale_price';
            }
            if (Schema::hasColumn('tariffs', 'urgent_price')) {
                $columns[] = 'urgent_price';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('pricing_items', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('pricing_items', 'markup_wholesale_percent')) {
                $columns[] = 'markup_wholesale_percent';
            }
            if (Schema::hasColumn('pricing_items', 'wholesale_price')) {
                $columns[] = 'wholesale_price';
            }
            if (Schema::hasColumn('pricing_items', 'markup_vip_percent')) {
                $columns[] = 'markup_vip_percent';
            }
            if (Schema::hasColumn('pricing_items', 'vip_price')) {
                $columns[] = 'vip_price';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('pricing_histories', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('pricing_histories', 'markup_wholesale_percent')) {
                $columns[] = 'markup_wholesale_percent';
            }
            if (Schema::hasColumn('pricing_histories', 'wholesale_price')) {
                $columns[] = 'wholesale_price';
            }
            if (Schema::hasColumn('pricing_histories', 'markup_vip_percent')) {
                $columns[] = 'markup_vip_percent';
            }
            if (Schema::hasColumn('pricing_histories', 'vip_price')) {
                $columns[] = 'vip_price';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tariff_client_prices')) {
            Schema::create('tariff_client_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tariff_id')->constrained('tariffs')->cascadeOnDelete();
                $table->string('client_category');
                $table->decimal('price', 12, 2);
                $table->timestamps();

                $table->unique(['tariff_id', 'client_category']);
                $table->index('client_category');
            });
        }

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'price_type')) {
                $table->string('price_type')->default('retail')->after('category');
            }
        });

        Schema::table('tariffs', function (Blueprint $table) {
            if (! Schema::hasColumn('tariffs', 'wholesale_price')) {
                $table->decimal('wholesale_price', 12, 2)->nullable()->after('sale_price');
            }
            if (! Schema::hasColumn('tariffs', 'urgent_price')) {
                $table->decimal('urgent_price', 12, 2)->nullable()->after('wholesale_price');
            }
        });

        Schema::table('pricing_items', function (Blueprint $table) {
            if (! Schema::hasColumn('pricing_items', 'markup_wholesale_percent')) {
                $table->decimal('markup_wholesale_percent', 6, 2)->default(30)->after('markup_price');
            }
            if (! Schema::hasColumn('pricing_items', 'wholesale_price')) {
                $table->decimal('wholesale_price', 12, 4)->nullable()->after('markup_wholesale_percent');
            }
            if (! Schema::hasColumn('pricing_items', 'markup_vip_percent')) {
                $table->decimal('markup_vip_percent', 6, 2)->default(40)->after('wholesale_price');
            }
            if (! Schema::hasColumn('pricing_items', 'vip_price')) {
                $table->decimal('vip_price', 12, 4)->nullable()->after('markup_vip_percent');
            }
        });

        Schema::table('pricing_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('pricing_histories', 'markup_wholesale_percent')) {
                $table->decimal('markup_wholesale_percent', 6, 2)->nullable()->after('markup_price');
            }
            if (! Schema::hasColumn('pricing_histories', 'wholesale_price')) {
                $table->decimal('wholesale_price', 12, 4)->nullable()->after('markup_wholesale_percent');
            }
            if (! Schema::hasColumn('pricing_histories', 'markup_vip_percent')) {
                $table->decimal('markup_vip_percent', 6, 2)->nullable()->after('wholesale_price');
            }
            if (! Schema::hasColumn('pricing_histories', 'vip_price')) {
                $table->decimal('vip_price', 12, 4)->nullable()->after('markup_vip_percent');
            }
        });
    }
};
