<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();

            $table->string('dispatch_ref', 20)->unique();

            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->cascadeOnDelete();

            $table->foreignId('transport_company_id')
                  ->constrained('transport_companies')
                  ->cascadeOnDelete();

            $table->date('flight_date');
            $table->time('pickup_time')->nullable();
            $table->string('pickup_location', 500)->nullable();
            $table->string('dropoff_location', 500)->nullable();

            $table->unsignedInteger('total_pax')->default(0);

            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'delivered',
                'cancelled',
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamp('notified_at')->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
