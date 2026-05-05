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
                        // NOTE: partner_id is intentionally NOT stored here.
                        // Guides access the /guide panel, not /partner.
                        // The guide-to-partner relationship is via the Guide model itself.
                    ]
                );

                // If the user already existed (firstOrCreate found a match),
                // ensure guide_id is correctly linked.
                if (!$user->wasRecentlyCreated) {
                    $user->forceFill(['guide_id' => $guide->id])->saveQuietly();
                }

                // Sync to exactly 'guide' role — removes any stale roles
                // (e.g., transport, partner) the user may have had previously.
                $user->syncRoles(['guide']);

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
            if ($guide->isForceDeleting()) {
                $guide->user()->forceDelete();
            } else {
                $guide->user()->delete();
            }
        });
    }
}
