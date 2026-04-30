<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Partner;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_ref',
        'type',
        'partner_id',
        'guide_id',
        'product_id',
        'flight_date',
        'flight_time',
        'adult_pax',
        'child_pax',
        'booking_source',
        'pickup_location',
        'dropoff_location',
        'partner_reference',
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
        'attended_pax',
        'attendance',
        'cancelled_reason',
        'notes',
        'created_by',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'invoice_id',
        'invoiced_at',
    ];

    protected function casts(): array
    {
        return [
            'flight_date'        => 'date',
            'confirmed_at'       => 'datetime',
            'cancelled_at'       => 'datetime',
            'adult_pax'          => 'integer',
            'child_pax'          => 'integer',
            'base_adult_price'   => 'decimal:2',
            'base_child_price'   => 'decimal:2',
            'adult_total'        => 'decimal:2',
            'child_total'        => 'decimal:2',
            'discount_amount'    => 'decimal:2',
            'final_amount'       => 'decimal:2',
            'amount_paid'        => 'decimal:2',
            'balance_due'        => 'decimal:2',
            'attended_pax'       => 'integer',
            'attendance'         => 'string',
            'pickup_location'    => 'string',
            'dropoff_location'   => 'string',
            'partner_reference'  => 'string',
            'invoiced_at'        => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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

    public function dispatch(): HasOne
    {
        return $this->hasOne(Dispatch::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
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

    public function isShow(): bool
    {
        return $this->attendance === 'show';
    }

    public function isNoShow(): bool
    {
        return $this->attendance === 'no_show';
    }

    public function getAttendanceColor(): string
    {
        return match ($this->attendance) {
            'show'    => 'success',
            'no_show' => 'danger',
            default   => 'gray',
        };
    }

    /**
     * Returns the number of passengers that actually showed up.
     * Greeter override (attended_pax) takes precedence over per-row customer count.
     */
    public function getShowedPax(): int
    {
        if ($this->attended_pax !== null) {
            return $this->attended_pax;
        }
        return $this->customers->where('attendance', 'show')->count();
    }

    /**
     * Returns count of passenger records that were filed (may be less than total PAX).
     */
    public function getFiledCustomerCount(): int
    {
        return $this->customers->count();
    }

    /**
     * Returns per-PAX attendance summary array.
     * total = booking PAX (adult_pax + child_pax) — NOT customer row count.
     * show  = attended_pax override if set, else customer rows marked show.
     * ['total' => 3, 'show' => 2, 'no_show' => 0, 'pending' => 1, 'filed' => 1]
     */
    public function getPaxAttendanceSummary(): array
    {
        $customers  = $this->customers;
        $totalPax   = $this->getTotalPax();  // booking-level: adult_pax + child_pax
        $showedPax  = $this->getShowedPax(); // override or per-row count
        $noShowPax  = $customers->where('attendance', 'no_show')->count();
        $filedCount = $customers->count();

        return [
            'total'   => $totalPax,
            'show'    => $showedPax,
            'no_show' => $noShowPax,
            'pending' => max(0, $totalPax - $showedPax - $noShowPax),
            'filed'   => $filedCount,
        ];
    }

    /**
     * Formatted label: "2/3 Showed" or "⏳ Awaiting" if none marked yet.
     * Denominator is always the booking's total PAX (adult + child), not filed-customer count.
     */
    public function getPaxAttendanceLabel(): string
    {
        $totalPax  = $this->getTotalPax();
        $showedPax = $this->getShowedPax();

        if ($totalPax === 0) {
            return '—';
        }
        if ($showedPax === 0 && $this->customers->where('attendance', 'no_show')->count() === 0 && $this->attended_pax === null) {
            return '⏳ Awaiting';
        }
        return "✅ {$showedPax}/{$totalPax} Showed";
    }

    public function isInvoiced(): bool
    {
        return $this->invoiced_at !== null;
    }
}
