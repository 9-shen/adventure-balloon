<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 8 — Partner Booking System
     * Adds partner_id FK to the bookings table so partner bookings can reference
     * the partner who made the booking (type = 'partner').
     * Regular bookings leave this NULL.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add partner_id after the 'type' column — nullable (NULL for regular bookings)
            $table->foreignId('partner_id')
                  ->nullable()
                  ->after('type')
                  ->constrained('partners')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Partner::class);
            $table->dropColumn('partner_id');
        });
    }
};
