<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_ref',
        'type',
        'product_id',
        'flight_date',
        'flight_time',
        'adult_pax',
        'child_pax',
        'booking_source',
        'base_adult_price',
        'base_child_price',
        'adult_total',
        'child_total',
        'discount_amount',
        'discount_reason',
        'final_amount',
        'payment_method',
        'payment_status',
        'amount_paid',
        'balance_due',
        'booking_status',
        'cancelled_reason',
        'notes',
        'created_by',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'flight_date'       => 'date',
            'confirmed_at'      => 'datetime',
            'cancelled_at'      => 'datetime',
            'adult_pax'         => 'integer',
            'child_pax'         => 'integer',
            'base_adult_price'  => 'decimal:2',
            'base_child_price'  => 'decimal:2',
            'adult_total'       => 'decimal:2',
            'child_total'       => 'decimal:2',
            'discount_amount'   => 'decimal:2',
            'final_amount'      => 'decimal:2',
            'amount_paid'       => 'decimal:2',
            'balance_due'       => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(BookingCustomer::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getTotalPax(): int
    {
        return $this->adult_pax + $this->child_pax;
    }

    public function isPending(): bool
    {
        return $this->booking_status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->booking_status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->booking_status === 'completed';
    }

    public function getStatusColor(): string
    {
        return match ($this->booking_status) {
            'confirmed'  => 'success',
            'cancelled'  => 'danger',
            'completed'  => 'info',
            default      => 'warning',
        };
    }

    public function getPaymentStatusColor(): string
    {
        return match ($this->payment_status) {
            'paid'    => 'success',
            'partial' => 'warning',
            'on_site' => 'info',
            default   => 'danger',
        };
    }
}
