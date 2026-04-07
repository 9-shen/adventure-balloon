<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'full_name',
        'email',
        'phone',
        'nationality',
        'passport_number',
        'date_of_birth',
        'weight_kg',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'weight_kg'     => 'decimal:2',
            'is_primary'    => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
