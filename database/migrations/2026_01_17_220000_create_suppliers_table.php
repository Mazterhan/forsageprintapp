<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('code')->unique();
            $table->string('status', 20)->default('active');
            $table->string('type', 50)->nullable();
            $table->string('category', 100)->nullable();
            $table->text('notes')->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('phones')->nullable();
            $table->string('emails')->nullable();
            $table->string('messengers')->nullable();
            $table->string('website')->nullable();
            $table->string('work_hours')->nullable();
            $table->string('portals')->nullable();

            $table->string('warehouse_address')->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('region')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->string('delivery_time')->nullable();
            $table->string('warehouse_contacts')->nullable();

            $table->string('legal_entity')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('vat_status')->nullable();
            $table->string('registration_address')->nullable();
            $table->string('bank_iban')->nullable();
            $table->string('bank_mfo')->nullable();
            $table->string('legal_address')->nullable();

            $table->string('currency', 10)->nullable();
            $table->decimal('default_discount', 5, 2)->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('min_order')->nullable();
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->string('return_terms')->nullable();

            $table->string('contract_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->string('contract_status')->nullable();
            $table->string('contract_file_path')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
