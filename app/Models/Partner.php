<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Partner extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'company_name',
        'trade_name',
        'registration_number',
        'tax_number',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'bank_name',
        'bank_account',
        'bank_iban',
        'bank_swift',
        'payment_terms_days',
        'status',
        'approved_at',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'approved_at'  => 'datetime',
            'payment_terms_days' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    /**
     * Products with custom adult/child pricing for this partner.
     * Access pivot fields via: $partner->products->first()->pivot->partner_adult_price
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'partner_products')
                    ->withPivot(['partner_adult_price', 'partner_child_price', 'is_active'])
                    ->withTimestamps()
                    ->using(PartnerProduct::class);
    }

    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Users linked to this partner company.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles')
                    ->where('role_id', function ($q) {
                        $q->select('id')
                          ->from('roles')
                          ->where('name', 'partner');
                    });
    }

    // ─── Media ───────────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('kyc-documents')
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);

        $this->addMediaCollection('partner-logo')
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getPricingForProduct(int $productId): ?PartnerProduct
    {
        return PartnerProduct::where('partner_id', $this->id)
                             ->where('product_id', $productId)
                             ->where('is_active', true)
                             ->first();
    }
}
