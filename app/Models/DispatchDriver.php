<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchDriver extends Model
{
    use HasFactory;

    protected $table = 'dispatch_drivers';

    protected $fillable = [
        'dispatch_id',
        'driver_id',
        'vehicle_id',
        'pax_assigned',
        'status',
        'whatsapp_sent',
        'whatsapp_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'pax_assigned'     => 'integer',
            'whatsapp_sent'    => 'boolean',
            'whatsapp_sent_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
