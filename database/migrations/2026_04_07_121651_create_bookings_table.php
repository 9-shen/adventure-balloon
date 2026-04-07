<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Reference & Type
            $table->string('booking_ref', 20)->unique();     // BLX-2026-0001
            $table->enum('type', ['regular', 'partner'])->default('regular');

            // Product & Flight
            $table->foreignId('product_id')->constrained('products');
            $table->date('flight_date');
            $table->time('flight_time')->nullable();
            $table->unsignedInteger('adult_pax')->default(1);
            $table->unsignedInteger('child_pax')->default(0);
            $table->string('booking_source')->nullable(); // walk-in, phone, website, email, referral

            // Price Snapshot (captured at time of booking — never changes)
            $table->decimal('base_adult_price', 10, 2);
            $table->decimal('base_child_price', 10, 2)->default(0);

            // Totals
            $table->decimal('adult_total', 10, 2);
            $table->decimal('child_total', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('final_amount', 10, 2);

            // Payment
            $table->enum('payment_method', ['cash', 'wire', 'online'])->default('cash');
            $table->enum('payment_status', ['due', 'partial', 'paid', 'on_site'])->default('due');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);  // final_amount - amount_paid

            // Booking Status
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('cancelled_reason')->nullable();
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
