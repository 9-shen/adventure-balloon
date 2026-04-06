<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transport_company_id',
        'make',
        'model',
        'plate_number',
        'capacity',
        'vehicle_type',
        'price_per_trip',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity'       => 'integer',
            'price_per_trip' => 'decimal:2',
            'is_active'      => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    /**
     * Drivers assigned to this vehicle.
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_vehicle')
                    ->withPivot('is_default')
                    ->withTimestamps();
    }
}
