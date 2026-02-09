<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->string('type_class')->nullable()->after('category');
            $table->string('film_brand_series')->nullable()->after('type_class');
            $table->decimal('roll_width_m', 10, 3)->nullable()->after('film_brand_series');
            $table->decimal('roll_length_m', 10, 3)->nullable()->after('roll_width_m');
            $table->decimal('sheet_thickness_mm', 10, 3)->nullable()->after('roll_length_m');
            $table->decimal('sheet_width_mm', 10, 3)->nullable()->after('sheet_thickness_mm');
            $table->decimal('sheet_length_mm', 10, 3)->nullable()->after('sheet_width_mm');
            $table->string('color')->nullable()->after('sheet_length_mm');
            $table->string('finish')->nullable()->after('color');
            $table->string('special_effect')->nullable()->after('finish');
            $table->string('liner')->nullable()->after('special_effect');
            $table->string('double_sided')->default('односторонній')->after('liner');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn([
                'type_class',
                'film_brand_series',
                'roll_width_m',
                'roll_length_m',
                'sheet_thickness_mm',
                'sheet_width_mm',
                'sheet_length_mm',
                'color',
                'finish',
                'special_effect',
                'liner',
                'double_sided',
            ]);
        });
    }
};
