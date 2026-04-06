<?php

namespace App\Services;

use App\Models\BlackoutDate;
use Carbon\Carbon;

class ProductAvailabilityService
{
    /**
     * Check if a given date is blocked for a product.
     *
     * Returns true if:
     *  - There is a global blackout (product_id = NULL) for this date, OR
     *  - There is a product-specific blackout for $productId on this date
     *
     * @param int|null $productId  Pass null to check global-only blackouts
     */
    public function isDateBlocked(?int $productId, Carbon $date): bool
    {
        return BlackoutDate::query()
            ->whereDate('date', $date)
            ->where(function ($query) use ($productId) {
                $query->whereNull('product_id'); // global blackout
                if ($productId !== null) {
                    $query->orWhere('product_id', $productId);
                }
            })
            ->exists();
    }

    /**
     * Get all blocked dates for a product in a given month.
     * Used for availability calendar display (Phase 7 will extend this).
     *
     * @return \Illuminate\Support\Collection<\Carbon\Carbon>
     */
    public function getBlockedDatesForMonth(?int $productId, Carbon $month): \Illuminate\Support\Collection
    {
        return BlackoutDate::query()
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->where(function ($query) use ($productId) {
                $query->whereNull('product_id');
                if ($productId !== null) {
                    $query->orWhere('product_id', $productId);
                }
            })
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date));
    }
}
