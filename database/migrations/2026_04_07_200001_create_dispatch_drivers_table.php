<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_drivers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dispatch_id')
                  ->constrained('dispatches')
                  ->cascadeOnDelete();

            $table->foreignId('driver_id')
                  ->constrained('drivers')
                  ->cascadeOnDelete();

            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->cascadeOnDelete();

            $table->unsignedInteger('pax_assigned')->default(0);

            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'delivered',
            ])->default('pending');

            $table->boolean('whatsapp_sent')->default(false);
            $table->timestamp('whatsapp_sent_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_drivers');
    }
};
