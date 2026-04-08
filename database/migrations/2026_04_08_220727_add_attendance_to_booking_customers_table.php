<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_customers', function (Blueprint $table) {
            $table->enum('attendance', ['pending', 'show', 'no_show'])
                  ->default('pending')
                  ->after('is_primary');
        });
    }

    public function down(): void
    {
        Schema::table('booking_customers', function (Blueprint $table) {
            $table->dropColumn('attendance');
        });
    }
};
