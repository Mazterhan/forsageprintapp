<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            if (! Schema::hasColumn('tariffs', 'name')) {
                $table->string('name')->nullable()->after('internal_code');
            }
            if (! Schema::hasColumn('tariffs', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
            if (! Schema::hasColumn('tariffs', 'subcontractor_id')) {
                $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors')->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            if (Schema::hasColumn('tariffs', 'subcontractor_id')) {
                $table->dropConstrainedForeignId('subcontractor_id');
            }
            if (Schema::hasColumn('tariffs', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('tariffs', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
