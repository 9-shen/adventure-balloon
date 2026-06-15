<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Suffix existing soft-deleted users
        DB::table('users')
            ->whereNotNull('deleted_at')
            ->where('email', 'not like', '%_deleted_%')
            ->get(['id', 'email', 'deleted_at'])
            ->each(function ($record) {
                $suffix = '_deleted_' . strtotime($record->deleted_at);
                DB::table('users')
                    ->where('id', $record->id)
                    ->update(['email' => $record->email . $suffix]);
            });

        // 2. Suffix existing soft-deleted guides
        DB::table('guides')
            ->whereNotNull('deleted_at')
            ->where('email', 'not like', '%_deleted_%')
            ->get(['id', 'email', 'deleted_at'])
            ->each(function ($record) {
                $suffix = '_deleted_' . strtotime($record->deleted_at);
                DB::table('guides')
                    ->where('id', $record->id)
                    ->update(['email' => $record->email . $suffix]);
            });

        // 3. Suffix existing soft-deleted drivers
        DB::table('drivers')
            ->whereNotNull('deleted_at')
            ->whereNotNull('email')
            ->where('email', 'not like', '%_deleted_%')
            ->get(['id', 'email', 'deleted_at'])
            ->each(function ($record) {
                $suffix = '_deleted_' . strtotime($record->deleted_at);
                DB::table('drivers')
                    ->where('id', $record->id)
                    ->update(['email' => $record->email . $suffix]);
            });

        // 4. Suffix existing soft-deleted balloon_dispatchers
        DB::table('balloon_dispatchers')
            ->whereNotNull('deleted_at')
            ->whereNotNull('email')
            ->where('email', 'not like', '%_deleted_%')
            ->get(['id', 'email', 'deleted_at'])
            ->each(function ($record) {
                $suffix = '_deleted_' . strtotime($record->deleted_at);
                DB::table('balloon_dispatchers')
                    ->where('id', $record->id)
                    ->update(['email' => $record->email . $suffix]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse suffixing is not strictly necessary or simple due to original emails not being tracked individually in the suffix,
        // but we can strip the suffix if it matches the pattern.
        $tables = ['users', 'guides', 'drivers', 'balloon_dispatchers'];
        foreach ($tables as $table) {
            DB::table($table)
                ->where('email', 'like', '%_deleted_%')
                ->get(['id', 'email'])
                ->each(function ($record) use ($table) {
                    $originalEmail = explode('_deleted_', $record->email)[0];
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['email' => $originalEmail]);
                });
        }
    }
};
