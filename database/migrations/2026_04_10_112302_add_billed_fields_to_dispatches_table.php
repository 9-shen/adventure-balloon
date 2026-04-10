<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->foreignId('transport_bill_id')->nullable()->after('cost_notes')->constrained('transport_bills')->nullOnDelete();
            $table->timestamp('billed_at')->nullable()->after('transport_bill_id');
        });
    }

    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropForeign(['transport_bill_id']);
            $table->dropColumn(['transport_bill_id', 'billed_at']);
        });
    }
};
