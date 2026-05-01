<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Driver extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Notifiable, InteractsWithMedia;

    protected $fillable = [
        'transport_company_id',
        'vehicle_id',
        'name',
        'email',
        'phone',
        'national_id',
        'license_number',
        'license_expiry',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'is_active'      => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'driver_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    /**
     * The single vehicle directly assigned to this driver (1:1).
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Vehicles assigned to this driver (with is_default flag on pivot).
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'driver_vehicle')
                    ->withPivot('is_default')
                    ->withTimestamps();
    }

    /**
     * Get the driver's default vehicle, or null.
     */
    public function defaultVehicle(): ?Vehicle
    {
        return $this->vehicles()
                    ->wherePivot('is_default', true)
                    ->first();
    }

    public function dispatchDriverRows(): HasMany
    {
        return $this->hasMany(DispatchDriver::class);
    }

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('license-documents')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isLicenseExpiringSoon(): bool
    {
        if (!$this->license_expiry) return false;
        return $this->license_expiry->diffInDays(now()) <= 30;
    }

    protected static function booted(): void
    {
        static::created(function (Driver $driver) {
            if ($driver->email) {
                $rawPassword = '1234567890';
                
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $driver->email],
                    [
                        'name' => $driver->name,
                        'password' => \Illuminate\Support\Facades\Hash::make($rawPassword),
                        'phone' => $driver->phone,
                        'national_id' => $driver->national_id,
                        'is_active' => $driver->is_active,
                        'driver_id' => $driver->id,
                        'transport_company_id' => $driver->transport_company_id,
                    ]
                );

                if (!$user->hasRole('driver')) {
                    $user->assignRole('driver');
                }

                try {
                    $driver->notify(new \App\Notifications\DriverAccountCreatedNotification($driver->name, $driver->email, $rawPassword));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to notify driver: " . $e->getMessage());
                }
            }
        });

        static::updated(function (Driver $driver) {
            if ($driver->isDirty(['name', 'email', 'phone', 'national_id', 'is_active'])) {
                if ($user = $driver->user) {
                    $user->fill($driver->only(['name', 'email', 'phone', 'national_id', 'is_active']))->saveQuietly();
                }
            }
        });

        static::deleting(function (Driver $driver) {
            if ($driver->isForceDeleting()) {
                $driver->user()->forceDelete();
            } else {
                $driver->user()->delete();
            }
        });
    }
}
