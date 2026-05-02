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

class User extends Authenticatable implements FilamentUser, HasMedia, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, InteractsWithMedia, SoftDeletes;

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
        'partner_id',
        'transport_company_id',
        'driver_id',
        'guide_id',
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
            'guide'      => $this->hasRole('guide') && $this->guide_id !== null,
            'greeter'    => $this->hasRole('greeter'),
            'dispatcher' => $this->hasRole('dispatcher'),

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
