<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            
            $table->string('description');               // e.g. "Hot Air Balloon Experience"
            $table->date('flight_date');
            $table->unsignedInteger('adult_pax')->default(0);
            $table->unsignedInteger('child_pax')->default(0);
            $table->decimal('unit_price', 10, 2);        // base adult price (representative)
            $table->decimal('line_total', 10, 2);        // booking final_amount
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
