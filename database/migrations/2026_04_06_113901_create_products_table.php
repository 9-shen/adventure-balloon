<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_adult_price', 10, 2)->default(0.00);
            $table->decimal('base_child_price', 10, 2)->default(0.00);
            $table->unsignedInteger('duration_minutes')->nullable(); // informational only
            $table->boolean('is_active')->default(true);
            $table->softDeletes(); // global rule: soft deletes on everything
            $table->timestamps();
        });
        // NOTE: No max_pax column — daily capacity is global via PaxSettings::daily_pax_capacity
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
