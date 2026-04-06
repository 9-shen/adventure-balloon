<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for partner_products table.
 * Gives access to adult/child pricing attached to a Partner ↔ Product link.
 */
class PartnerProduct extends Pivot
{
    protected $table = 'partner_products';

    public $incrementing = true; // we have an 'id' column on this pivot

    protected $fillable = [
        'partner_id',
        'product_id',
        'partner_adult_price',
        'partner_child_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'partner_adult_price' => 'decimal:2',
            'partner_child_price' => 'decimal:2',
            'is_active'           => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
