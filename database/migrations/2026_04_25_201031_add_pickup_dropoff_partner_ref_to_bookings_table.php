<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Pick-up location (required at form level, nullable at DB level for existing rows)
            $table->string('pickup_location')->nullable()->after('booking_source');

            // Drop-off location (fully optional)
            $table->string('dropoff_location')->nullable()->after('pickup_location');

            // External partner reference — optional for regular, required for partner bookings
            $table->string('partner_reference', 100)->nullable()->after('dropoff_location');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_location', 'dropoff_location', 'partner_reference']);
        });
    }
};
