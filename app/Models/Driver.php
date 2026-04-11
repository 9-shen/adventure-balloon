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
        static::deleting(function (Driver $driver) {
            if ($driver->isForceDeleting()) {
                $driver->user()->forceDelete();
            } else {
                $driver->user()->delete();
            }
        });
    }
}
