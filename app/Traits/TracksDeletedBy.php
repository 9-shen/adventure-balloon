<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksDeletedBy
{
    /**
     * Boot the trait to hook into the model lifecycle events.
     *
     * @return void
     */
    public static function bootTracksDeletedBy(): void
    {
        static::deleting(function ($model) {
            // Only update deleted_by if it's not a force delete and there's an authenticated user
            if (Auth::check() && ! $model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                // Avoid firing other events while just saving the deleted_by column
                $model->saveQuietly();
            }
        });
    }

    /**
     * Relationship to the user who deleted the record.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }
}
