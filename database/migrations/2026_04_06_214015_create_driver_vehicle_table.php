<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_vehicle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')
                  ->constrained('drivers')
                  ->cascadeOnDelete();
            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->cascadeOnDelete();
            $table->boolean('is_default')->default(false); // driver's primary vehicle
            $table->timestamps();

            // A driver can only be assigned once to a given vehicle
            $table->unique(['driver_id', 'vehicle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_vehicle');
    }
};
