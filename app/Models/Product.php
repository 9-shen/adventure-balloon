<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    // Partner pricing pivot is set up in Phase 5
    // public function partners(): BelongsToMany { ... }

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
