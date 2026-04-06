<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'base_adult_price',
        'base_child_price',
        'duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_adult_price' => 'decimal:2',
            'base_child_price' => 'decimal:2',
            'is_active'        => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function blackoutDates(): HasMany
    {
        return $this->hasMany(BlackoutDate::class);
    }

    /**
     * Partners that have custom pricing for this product.
     */
    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_products')
                    ->withPivot(['partner_adult_price', 'partner_child_price', 'is_active'])
                    ->withTimestamps()
                    ->using(PartnerProduct::class);
    }

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-images')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(400)
             ->height(300)
             ->sharpen(10);
    }
}
