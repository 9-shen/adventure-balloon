<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'vehicles',
        'users',
        'transport_companies',
        'transport_bills',
        'products',
        'partners',
        'invoices',
        'guides',
        'drivers',
        'dispatches',
        'bookings'
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['deleted_by']);
                    $table->dropColumn('deleted_by');
                });
            }
        }
    }
};
