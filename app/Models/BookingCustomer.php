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
        'attendance',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'weight_kg'     => 'decimal:2',
            'is_primary'    => 'boolean',
            'attendance'    => 'string',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isShow(): bool
    {
        return $this->attendance === 'show';
    }

    public function isNoShow(): bool
    {
        return $this->attendance === 'no_show';
    }

    public function getAttendanceBadgeColor(): string
    {
        return match ($this->attendance) {
            'show'    => 'success',
            'no_show' => 'danger',
            default   => 'gray',
        };
    }

    public function getAttendanceBadgeLabel(): string
    {
        return match ($this->attendance) {
            'show'    => '✅ Show',
            'no_show' => '❌ No-Show',
            default   => '⏳ Pending',
        };
    }
}
