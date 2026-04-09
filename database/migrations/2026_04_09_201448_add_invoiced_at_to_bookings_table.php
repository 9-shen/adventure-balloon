<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('cancelled_at')
                  ->constrained('invoices')->nullOnDelete();
            $table->timestamp('invoiced_at')->nullable()->after('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropColumn('invoiced_at');
        });
    }
};
