<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'booking_id',
        'description',
        'flight_date',
        'adult_pax',
        'child_pax',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'flight_date' => 'date',
            'adult_pax'   => 'integer',
            'child_pax'   => 'integer',
            'unit_price'  => 'decimal:2',
            'line_total'  => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
