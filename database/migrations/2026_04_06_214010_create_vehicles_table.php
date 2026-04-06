<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_company_id')
                  ->constrained('transport_companies')
                  ->cascadeOnDelete();
            $table->string('make', 100);           // e.g. Mercedes
            $table->string('model', 100);          // e.g. Sprinter
            $table->string('plate_number', 50)->unique();
            $table->unsignedInteger('capacity');   // number of passengers
            $table->enum('vehicle_type', ['van', 'minibus', 'bus', 'car'])->default('van');
            $table->decimal('price_per_trip', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
