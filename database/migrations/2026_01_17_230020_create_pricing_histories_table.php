<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_histories', function (Blueprint $table) {
            $table->id();
            $table->string('internal_code')->index();
            $table->string('name');
            $table->string('category')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors');
            $table->decimal('import_price', 12, 4)->nullable();
            $table->decimal('markup_percent', 6, 2)->nullable();
            $table->decimal('markup_price', 12, 4)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at')->nullable();
            $table->string('source', 20)->default('manual');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_histories');
    }
};
