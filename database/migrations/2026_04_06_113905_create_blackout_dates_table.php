<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->nullable()               // NULL = global blackout (blocks ALL products)
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->timestamps();

            // Prevent duplicate blackout for same product+date
            $table->unique(['product_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blackout_dates');
    }
};
