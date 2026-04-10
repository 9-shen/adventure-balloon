<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_bill_id')->constrained('transport_bills')->cascadeOnDelete();
            $table->foreignId('dispatch_id')->constrained('dispatches');
            $table->string('description');
            $table->integer('vehicles_used')->default(1);
            $table->decimal('vehicle_cost', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_bill_items');
    }
};
