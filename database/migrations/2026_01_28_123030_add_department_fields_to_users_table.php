<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('role')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'department_category_id')) {
                $table->foreignId('department_category_id')->nullable()->after('department_id')->constrained('department_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'department_position_id')) {
                $table->foreignId('department_position_id')->nullable()->after('department_category_id')->constrained('department_positions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_position_id')) {
                $table->dropConstrainedForeignId('department_position_id');
            }
            if (Schema::hasColumn('users', 'department_category_id')) {
                $table->dropConstrainedForeignId('department_category_id');
            }
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
    }
};
