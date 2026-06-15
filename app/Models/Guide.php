<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletedBy;

class Guide extends Model
{
    use HasFactory, SoftDeletes, TracksDeletedBy;

    public static bool $isRestoringLinked = false;
    public static bool $isDeletingLinked = false;

    protected $fillable = [
        'partner_id',
        'name',
        'email',
        'phone',
        'guide_reference',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'guide_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'guide_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasPortalAccount(): bool
    {
        return $this->user()->exists();
    }

    protected static function booted(): void
    {
        static::created(function (Guide $guide) {
            if ($guide->email) {
                $rawPassword = '1234567890';

                $user = \App\Models\User::firstOrCreate(
                    ['email' => $guide->email],
                    [
                        'name'      => $guide->name,
                        'password'  => \Illuminate\Support\Facades\Hash::make($rawPassword),
                        'phone'     => $guide->phone,
                        'is_active' => $guide->is_active,
                        'guide_id'  => $guide->id,
                        'partner_id' => $guide->partner_id,
                    ]
                );

                // If the user already existed (firstOrCreate found a match),
                // ensure guide_id and partner_id are correctly linked.
                if (!$user->wasRecentlyCreated) {
                    $user->forceFill([
                        'guide_id' => $guide->id,
                        'partner_id' => $guide->partner_id,
                    ])->saveQuietly();
                }

                // Assign 'guide' role safely
                if (!$user->hasRole('guide')) {
                    $user->assignRole('guide');
                }

                try {
                    $guide->notify(new \App\Notifications\GuideAccountCreatedNotification($guide->name, $guide->email, $rawPassword));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to notify guide: " . $e->getMessage());
                }
            }
        });

        static::updated(function (Guide $guide) {
            if ($guide->isDirty(['name', 'email', 'phone', 'is_active'])) {
                if ($user = $guide->user) {
                    $user->fill($guide->only(['name', 'email', 'phone', 'is_active']))->saveQuietly();
                }
            }
        });

        static::deleting(function (Guide $guide) {
            // Suffix email and guide_reference to free them up (always run, even if triggered by cascading delete)
            if ($guide->email && !str_contains($guide->email, '_deleted_')) {
                $guide->email = $guide->email . '_deleted_' . time();
                $guide->saveQuietly();
            }
            if ($guide->guide_reference && !str_contains($guide->guide_reference, '_deleted_')) {
                $guide->guide_reference = $guide->guide_reference . '_deleted_' . time();
                $guide->saveQuietly();
            }

            if (static::$isDeletingLinked) {
                return;
            }
            static::$isDeletingLinked = true;

            try {
                if ($guide->isForceDeleting()) {
                    if ($user = User::withTrashed()->where('guide_id', $guide->id)->first()) {
                        User::$isDeletingLinked = true;
                        $user->forceDelete();
                    }
                } else {
                    if ($user = $guide->user) {
                        User::$isDeletingLinked = true;
                        $user->delete();
                    }
                }
            } finally {
                static::$isDeletingLinked = false;
                User::$isDeletingLinked = false;
            }
        });

        static::restoring(function (Guide $guide) {
            if (static::$isRestoringLinked) {
                return;
            }
            static::$isRestoringLinked = true;

            try {
                if ($guide->email && str_contains($guide->email, '_deleted_')) {
                    $originalEmail = explode('_deleted_', $guide->email)[0];
                    if (static::where('email', $originalEmail)->exists()) {
                        throw new \Exception("Cannot restore guide: The email '{$originalEmail}' is already taken by another active guide.");
                    }
                    $guide->email = $originalEmail;
                    $guide->saveQuietly();
                }

                if ($guide->guide_reference && str_contains($guide->guide_reference, '_deleted_')) {
                    $originalRef = explode('_deleted_', $guide->guide_reference)[0];
                    if (static::where('partner_id', $guide->partner_id)->where('guide_reference', $originalRef)->exists()) {
                        throw new \Exception("Cannot restore guide: The reference '{$originalRef}' is already taken by another active guide for this partner.");
                    }
                    $guide->guide_reference = $originalRef;
                    $guide->saveQuietly();
                }

                if ($user = User::onlyTrashed()->where('guide_id', $guide->id)->first()) {
                    User::$isRestoringLinked = true;
                    $user->restore();
                }
            } finally {
                static::$isRestoringLinked = false;
                User::$isRestoringLinked = false;
            }
        });
    }
}
