<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dispatch_ref',
        'booking_id',
        'transport_company_id',
        'flight_date',
        'pickup_time',
        'pickup_location',
        'dropoff_location',
        'total_pax',
        'status',
        'notes',
        'transport_cost',
        'cost_notes',
        'transport_bill_id',
        'billed_at',
        'notified_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'flight_date'     => 'date',
            'notified_at'     => 'datetime',
            'billed_at'       => 'datetime',
            'total_pax'       => 'integer',
            'transport_cost'  => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Direct pivot rows (DispatchDriver model) — use for loading driver details.
     */
    public function dispatchDriverRows(): HasMany
    {
        return $this->hasMany(DispatchDriver::class);
    }

    /**
     * BelongsToMany shortcut for driver names etc.
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'dispatch_drivers')
                    ->withPivot('vehicle_id', 'pax_assigned', 'status', 'whatsapp_sent', 'whatsapp_sent_at')
                    ->withTimestamps();
    }

    public function transportBill(): BelongsTo
    {
        return $this->belongsTo(TransportBill::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isNotified(): bool
    {
        return $this->notified_at !== null;
    }

    public function isBilled(): bool
    {
        return $this->billed_at !== null;
    }

    /**
     * Calculate transport cost from the sum of each assigned vehicle's price_per_trip.
     */
    public function calculateCost(): float
    {
        return (float) $this->dispatchDriverRows()
            ->with('vehicle')
            ->get()
            ->sum(fn($row) => (float) ($row->vehicle?->price_per_trip ?? 0));
    }
}
