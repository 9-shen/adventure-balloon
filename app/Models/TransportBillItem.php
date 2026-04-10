<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportBillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_bill_id',
        'dispatch_id',
        'description',
        'vehicles_used',
        'vehicle_cost',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'vehicles_used' => 'integer',
            'vehicle_cost'  => 'decimal:2',
            'line_total'    => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function transportBill(): BelongsTo
    {
        return $this->belongsTo(TransportBill::class);
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }
}
