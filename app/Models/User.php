<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\TracksDeletedBy;

class User extends Authenticatable implements FilamentUser, HasMedia, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, InteractsWithMedia, SoftDeletes, TracksDeletedBy;

    public static bool $isRestoringLinked = false;
    public static bool $isDeletingLinked = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'national_id',
        'nationality',
        'date_of_birth',
        'address',
        'is_active',
        'last_login_at',
        // Portal FK columns — required so model observers can assign these without being blocked by mass-assignment protection
        'partner_id',
        'driver_id',
        'guide_id',
        'transport_company_id',
        'balloon_dispatcher_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Allow users to access Filament panels based on their role.
     * Partner users may only access the 'partner' panel.
     * All other staff roles may only access the 'admin' panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            // /admin — super_admin and admin ONLY — full access to everything
            'admin'      => $this->hasAnyRole(['super_admin', 'admin']),

            // /accountant — accountant role ONLY
            'accountant' => $this->hasRole('accountant'),

            // /manager — manager + admin/super_admin for oversight
            'manager'    => $this->hasAnyRole(['manager', 'admin', 'super_admin']),

            // Each portal role is locked to its own portal only:
            'partner'    => $this->hasRole('partner') && $this->partner_id !== null,
            'transport'  => $this->hasRole('transport') && $this->transport_company_id !== null,
            'driver'     => $this->hasRole('driver') && $this->driver_id !== null,
            'guide'               => $this->hasRole('guide') && $this->guide_id !== null,
            'greeter'             => $this->hasRole('greeter'),
            'dispatcher'          => $this->hasRole('dispatcher'),
            'balloon-dispatcher'  => $this->hasRole('balloon_dispatcher'),

            default      => false,  // explicit deny-all
        };
    }

    // ─── Events ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->isDirty(['name', 'email', 'phone', 'national_id', 'is_active'])) {
                if ($user->driver) {
                    $user->driver->fill($user->only(['name', 'email', 'phone', 'national_id', 'is_active']))->saveQuietly();
                }
                if ($user->guide) {
                    $user->guide->fill($user->only(['name', 'email', 'phone', 'is_active']))->saveQuietly();
                }
            }
        });

        static::deleting(function (User $user) {
            // Suffix email to free it up for reuse (always run, even if triggered by cascading delete)
            if ($user->email && !str_contains($user->email, '_deleted_')) {
                $user->email = $user->email . '_deleted_' . time();
                $user->saveQuietly();
            }

            // Clear Spatie Permission cache (always run)
            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to clear permission cache: " . $e->getMessage());
            }

            if (static::$isDeletingLinked) {
                return;
            }
            static::$isDeletingLinked = true;

            try {
                if ($user->isForceDeleting()) {
                    // Cascade force delete profiles
                    if ($user->driver_id) {
                        Driver::withTrashed()->find($user->driver_id)?->forceDelete();
                    }
                    if ($user->guide_id) {
                        Guide::withTrashed()->find($user->guide_id)?->forceDelete();
                    }
                    if ($user->balloon_dispatcher_id) {
                        BalloonDispatcher::withTrashed()->find($user->balloon_dispatcher_id)?->forceDelete();
                    }
                } else {
                    // Cascade soft delete profiles
                    if ($user->driver_id && ($driver = Driver::find($user->driver_id))) {
                        Driver::$isDeletingLinked = true;
                        $driver->delete();
                    }
                    if ($user->guide_id && ($guide = Guide::find($user->guide_id))) {
                        Guide::$isDeletingLinked = true;
                        $guide->delete();
                    }
                    if ($user->balloon_dispatcher_id && ($dispatcher = BalloonDispatcher::find($user->balloon_dispatcher_id))) {
                        BalloonDispatcher::$isDeletingLinked = true;
                        $dispatcher->delete();
                    }
                }
            } finally {
                static::$isDeletingLinked = false;
                Driver::$isDeletingLinked = false;
                Guide::$isDeletingLinked = false;
                BalloonDispatcher::$isDeletingLinked = false;
            }
        });

        static::restoring(function (User $user) {
            if (static::$isRestoringLinked) {
                return;
            }
            static::$isRestoringLinked = true;

            try {
                if ($user->email && str_contains($user->email, '_deleted_')) {
                    $originalEmail = explode('_deleted_', $user->email)[0];
                    if (static::where('email', $originalEmail)->exists()) {
                        throw new \Exception("Cannot restore user: The email '{$originalEmail}' is already taken by another active user.");
                    }
                    $user->email = $originalEmail;
                    $user->saveQuietly();
                }

                // Restore linked profiles
                if ($user->driver_id) {
                    Driver::$isRestoringLinked = true;
                    Driver::onlyTrashed()->find($user->driver_id)?->restore();
                }
                if ($user->guide_id) {
                    Guide::$isRestoringLinked = true;
                    Guide::onlyTrashed()->find($user->guide_id)?->restore();
                }
                if ($user->balloon_dispatcher_id) {
                    BalloonDispatcher::$isRestoringLinked = true;
                    BalloonDispatcher::onlyTrashed()->find($user->balloon_dispatcher_id)?->restore();
                }
            } finally {
                static::$isRestoringLinked = false;
                Driver::$isRestoringLinked = false;
                Guide::$isRestoringLinked = false;
                BalloonDispatcher::$isRestoringLinked = false;
            }
        });

        static::forceDeleting(function (User $user) {
            // Nullify transport_bills.created_by to prevent RESTRICT FK constraint errors
            // since the transport_bills migration didn't specify nullOnDelete()
            \Illuminate\Support\Facades\DB::table('transport_bills')
                ->where('created_by', $user->id)
                ->update(['created_by' => null]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────────────

    public function partner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function transportCompany(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function driver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function guide(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function balloonDispatcher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BalloonDispatcher::class);
    }

    public function managedPartners(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'dispatcher_partner', 'user_id', 'partner_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }
}
