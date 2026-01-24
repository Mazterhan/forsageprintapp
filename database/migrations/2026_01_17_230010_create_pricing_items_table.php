<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_items', function (Blueprint $table) {
            $table->id();
            $table->string('internal_code')->index();
            $table->string('external_code')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors');
            $table->decimal('import_price', 12, 4)->nullable();
            $table->decimal('markup_percent', 6, 2)->default(30);
            $table->decimal('markup_price', 12, 4)->nullable();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supplier_id', 'internal_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_items');
    }
};
