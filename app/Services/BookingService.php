<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Product;
use App\Settings\PaxSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    // ─── Reference Generator ─────────────────────────────────────────────────

    /**
     * Generate a unique booking reference: BLX-2026-0001
     * Sequential per year — resets to 0001 each January 1.
     */
    public function generateRef(): string
    {
        $year = now()->year;

        // Count bookings for current year (including soft-deleted, to avoid gaps)
        $count = Booking::withTrashed()
            ->whereYear('created_at', $year)
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $ref = "BLX-{$year}-{$sequence}";

        // Safety: ensure uniqueness (extremely rare collision if creating simultaneously)
        while (Booking::withTrashed()->where('booking_ref', $ref)->exists()) {
            $count++;
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $ref = "BLX-{$year}-{$sequence}";
        }

        return $ref;
    }

    // ─── PAX Availability ────────────────────────────────────────────────────

    /**
     * How many PAX remain available for a given date.
     * Uses PaxSettings::daily_pax_capacity (DB setting, default 250).
     */
    public function getAvailablePax(Carbon $date): int
    {
        $capacity = app(PaxSettings::class)->daily_pax_capacity ?? 250;

        $usedRegular = Booking::whereDate('flight_date', $date)
            ->whereIn('booking_status', ['pending', 'confirmed'])
            ->selectRaw('SUM(adult_pax + child_pax) as total')
            ->value('total') ?? 0;

        // Phase 8 will add partner_bookings here
        $used = $usedRegular;

        return max(0, $capacity - $used);
    }

    /**
     * Returns true if there is enough capacity for the requested PAX on the given date.
     */
    public function checkAvailability(Carbon $date, int $pax): bool
    {
        return $this->getAvailablePax($date) >= $pax;
    }

    // ─── Pricing Calculator ──────────────────────────────────────────────────

    /**
     * Calculate all pricing fields from a product + PAX counts + discount.
     * Returns array ready to merge into booking data.
     */
    public function calculatePricing(Product $product, int $adultPax, int $childPax, float $discount = 0): array
    {
        $adultTotal  = round($product->base_adult_price * $adultPax, 2);
        $childTotal  = round($product->base_child_price * $childPax, 2);
        $finalAmount = round($adultTotal + $childTotal - $discount, 2);

        return [
            'base_adult_price' => $product->base_adult_price,
            'base_child_price' => $product->base_child_price,
            'adult_total'      => $adultTotal,
            'child_total'      => $childTotal,
            'final_amount'     => max(0, $finalAmount),
        ];
    }

    // ─── Create Booking ───────────────────────────────────────────────────────

    /**
     * Create a booking + its customers inside a DB transaction.
     * $data must include a 'booking_customers' key with array of customer rows.
     *
     * @param  array  $data  — merged wizard data, already processed by mutateFormDataBeforeCreate
     * @return Booking
     */
    public function createBooking(array $data): Booking
    {
        $customers = $data['booking_customers'] ?? [];
        unset($data['booking_customers']);

        return DB::transaction(function () use ($data, $customers) {
            /** @var Booking $booking */
            $booking = Booking::create($data);

            foreach ($customers as $customer) {
                $booking->customers()->create($customer);
            }

            return $booking;
        });
    }
}
