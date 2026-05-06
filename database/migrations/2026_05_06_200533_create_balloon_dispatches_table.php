<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balloon_dispatches', function (Blueprint $table) {
            $table->id();
            $table->date('dispatch_date');
            $table->longText('content')->nullable();  // TipTap/RichEditor HTML output
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balloon_dispatches');
    }
};
