<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

            $table->enum('type', ['adult', 'child'])->default('adult');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('passport_number', 100)->nullable(); // optional
            $table->date('date_of_birth')->nullable();          // optional
            $table->decimal('weight_kg', 5, 2)->nullable();     // optional — balloon safety
            $table->boolean('is_primary')->default(false);      // main contact person

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_customers');
    }
};
