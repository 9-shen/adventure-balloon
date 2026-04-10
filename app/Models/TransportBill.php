<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bill_ref',
        'transport_company_id',
        'period_from',
        'period_to',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'sent_at',
        'paid_at',
        'payment_reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_from'   => 'date',
            'period_to'     => 'date',
            'subtotal'      => 'decimal:2',
            'tax_rate'      => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'total_amount'  => 'decimal:2',
            'amount_paid'   => 'decimal:2',
            'balance_due'   => 'decimal:2',
            'sent_at'       => 'datetime',
            'paid_at'       => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransportBillItem::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class, 'transport_bill_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft'   => 'gray',
            'sent'    => 'info',
            'paid'    => 'success',
            'overdue' => 'danger',
            default   => 'gray',
        };
    }

    /**
     * Generate the next TBL-YYYY-XXXX reference.
     */
    public static function generateRef(): string
    {
        $year    = now()->year;
        $prefix  = "TBL-{$year}-";
        $last    = static::withTrashed()
                         ->where('bill_ref', 'like', "{$prefix}%")
                         ->orderByDesc('bill_ref')
                         ->value('bill_ref');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
