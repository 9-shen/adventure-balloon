<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlackoutDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', // nullable — NULL means global blackout
        'date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    /**
     * nullable — NULL means this blackout applies to ALL products
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Check if a date is blocked for a given product (or globally)
     */
    public function scopeForDate($query, \Carbon\Carbon $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForProduct($query, ?int $productId)
    {
        return $query->where(function ($q) use ($productId) {
            $q->whereNull('product_id') // global blackout
              ->orWhere('product_id', $productId);
        });
    }
}
