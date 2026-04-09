<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_ref',
        'partner_id',
        'period_from',
        'period_to',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_amount',
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
            'period_from'  => 'date',
            'period_to'    => 'date',
            'subtotal'     => 'decimal:2',
            'tax_rate'     => 'decimal:2',
            'tax_amount'   => 'decimal:2',
            'total_amount' => 'decimal:2',
            'sent_at'      => 'datetime',
            'paid_at'      => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
     * Generate the next INV-YYYY-XXXX reference.
     */
    public static function generateRef(): string
    {
        $year    = now()->year;
        $prefix  = "INV-{$year}-";
        $last    = static::withTrashed()
                         ->where('invoice_ref', 'like', "{$prefix}%")
                         ->orderByDesc('invoice_ref')
                         ->value('invoice_ref');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
