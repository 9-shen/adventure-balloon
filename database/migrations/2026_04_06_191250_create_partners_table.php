<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('trade_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('bank_iban', 100)->nullable();
            $table->string('bank_swift', 50)->nullable();

            // Billing terms
            $table->unsignedInteger('payment_terms_days')->default(30);

            // Status workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();

            // Misc
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->softDeletes(); // global rule: soft deletes on everything
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
