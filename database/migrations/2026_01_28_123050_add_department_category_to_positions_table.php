<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('department_positions', 'department_category_id')) {
                $table->foreignId('department_category_id')->nullable()->after('department_id')->constrained('department_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('department_positions', function (Blueprint $table) {
            if (Schema::hasColumn('department_positions', 'department_category_id')) {
                $table->dropConstrainedForeignId('department_category_id');
            }
        });
    }
};
