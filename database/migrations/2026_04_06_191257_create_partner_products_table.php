<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                  ->constrained('partners')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->decimal('partner_adult_price', 10, 2)->default(0.00);
            $table->decimal('partner_child_price', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // A partner can only have one pricing row per product
            $table->unique(['partner_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_products');
    }
};
