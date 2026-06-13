<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getSchemaBuilder()->getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_method ENUM('cash', 'wire', 'online', 'l_c', 'voucher') DEFAULT 'cash'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this might drop data if they have 'l_c' or 'voucher' selected, so it's safer to keep the column as is, or revert back safely.
        // We will just recreate the original enum in down, but be careful with existing values.
        if (DB::getSchemaBuilder()->getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_method ENUM('cash', 'wire', 'online') DEFAULT 'cash'");
        }
    }
};
