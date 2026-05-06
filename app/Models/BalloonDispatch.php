<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\TracksDeletedBy;

class BalloonDispatch extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, TracksDeletedBy;

    protected $fillable = [
        'dispatch_date',
        'content',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_date' => 'date',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('balloon-dispatch-images')
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(600)
             ->height(400)
             ->sharpen(10);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getContentExcerpt(int $length = 120): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content ?? ''), $length);
    }

    public function hasImage(): bool
    {
        return $this->getFirstMedia('balloon-dispatch-images') !== null;
    }
}
