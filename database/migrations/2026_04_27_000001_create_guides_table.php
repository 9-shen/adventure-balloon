<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('email')->unique();           // required — used for portal login
            $table->string('phone', 50);
            $table->string('guide_reference', 100);     // required — e.g. GD-001

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // guide_reference unique per partner
            $table->unique(['partner_id', 'guide_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
