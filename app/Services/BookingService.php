<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Product;
use App\Notifications\PaxCapacityAlertNotification;
use App\Settings\AppSettings;
use App\Settings\PaxSettings;
use App\Settings\WhatsAppSettings;
use Carbon\Carbon;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class BookingService
{
    // ─── Reference Generator ─────────────────────────────────────────────────

    /**
     * Generate a unique booking reference: {PREFIX}-YYYY-NNNN
     * Each prefix (BLX = regular, PBX = partner) maintains its own
     * independent sequential counter that resets each January 1.
     *
     * @param  string  $prefix  e.g. 'BLX' or 'PBX'
     */
    public function generateRef(string $prefix = 'BLX'): string
    {
        $year = now()->year;

        // Count bookings for this prefix + year (including soft-deleted, to avoid gaps)
        $count = Booking::withTrashed()
            ->where('booking_ref', 'like', "{$prefix}-{$year}-%")
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $ref = "{$prefix}-{$year}-{$sequence}";

        // Safety: ensure uniqueness (collision guard for concurrent creates)
        while (Booking::withTrashed()->where('booking_ref', $ref)->exists()) {
            $count++;
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $ref = "{$prefix}-{$year}-{$sequence}";
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

        // Count ALL booking types (regular + partner) — both use the same daily cap
        $used = Booking::whereDate('flight_date', $date)
            ->whereIn('booking_status', ['pending', 'confirmed'])
            ->selectRaw('SUM(adult_pax + child_pax) as total')
            ->value('total') ?? 0;

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
     * Calculate all pricing fields from a product + PAX counts + optional partner pricing.
     *
     * When $partnerId is provided the method queries the partner_products pivot
     * for partner-specific adult/child prices. Falls back to base product prices
     * if no pivot row is found.
     *
     * Returns an array ready to merge into booking data.
     *
     * @param  int|null  $partnerId  If set, use partner-specific prices from pivot
     */
    public function calculatePricing(
        Product $product,
        int     $adultPax,
        int     $childPax,
        float   $discount = 0,
        ?int    $partnerId = null
    ): array {
        // Resolve prices: partner pivot first, fall back to base product price
        [$adultPrice, $childPrice] = $this->resolvePrices($product, $partnerId);

        $adultTotal  = round($adultPrice * $adultPax, 2);
        $childTotal  = round($childPrice * $childPax, 2);
        $finalAmount = round($adultTotal + $childTotal - $discount, 2);

        return [
            'base_adult_price' => $adultPrice,
            'base_child_price' => $childPrice,
            'adult_total'      => $adultTotal,
            'child_total'      => $childTotal,
            'final_amount'     => max(0, $finalAmount),
        ];
    }

    /**
     * Resolve adult + child unit prices.
     * Returns [adultPrice, childPrice] — partner pivot if available, else base product.
     *
     * @return array{0: float, 1: float}
     */
    private function resolvePrices(Product $product, ?int $partnerId): array
    {
        if ($partnerId) {
            $pivot = DB::table('partner_products')
                ->where('partner_id', $partnerId)
                ->where('product_id', $product->id)
                ->first();

            if ($pivot) {
                return [
                    (float) $pivot->partner_adult_price,
                    (float) $pivot->partner_child_price,
                ];
            }
        }

        return [
            (float) $product->base_adult_price,
            (float) $product->base_child_price,
        ];
    }

    // ─── Create Booking ───────────────────────────────────────────────────────

    /**
     * Create a booking + its customers inside a DB transaction.
     * After creation, runs a PAX capacity alert check (Phase 26-D).
     *
     * $data must include a 'booking_customers' key with array of customer rows.
     *
     * @param  array  $data  — merged wizard data, already processed by mutateFormDataBeforeCreate
     * @return Booking
     */
    public function createBooking(array $data): Booking
    {
        $customers = $data['booking_customers'] ?? [];
        unset($data['booking_customers']);

        $booking = DB::transaction(function () use ($data, $customers) {
            /** @var Booking $booking */
            $booking = Booking::create($data);

            foreach ($customers as $customer) {
                $booking->customers()->create($customer);
            }

            return $booking;
        });

        // ── Phase 26-D: Check PAX capacity and alert admin if threshold reached ──
        if ($booking->flight_date) {
            $this->checkAndAlertPaxCapacity(Carbon::parse($booking->flight_date));
        }

        return $booking;
    }

    // ─── Phase 26-D: PAX Capacity Alert ─────────────────────────────────────

    /**
     * After every booking creation, check if remaining PAX for the flight date
     * has reached or fallen below the warning threshold.
     *
     * If threshold is crossed, send:
     *   1. Email → company_email (AnonymousNotifiable)
     *   2. WhatsApp → company_phone (if WhatsApp enabled)
     *
     * Failures are logged and silently swallowed — never crash the booking flow.
     */
    public function checkAndAlertPaxCapacity(Carbon $date): void
    {
        try {
            /** @var PaxSettings $pax */
            $pax       = app(PaxSettings::class);
            $capacity  = $pax->daily_pax_capacity ?? 250;
            $threshold = $pax->warning_threshold  ?? 30;

            $used = Booking::whereDate('flight_date', $date)
                ->whereIn('booking_status', ['pending', 'confirmed'])
                ->selectRaw('SUM(adult_pax + child_pax) as total')
                ->value('total') ?? 0;

            $remaining = max(0, $capacity - (int) $used);

            // Only alert if threshold is crossed
            if ($remaining > $threshold) {
                return;
            }

            /** @var AppSettings $appSettings */
            $appSettings = app(AppSettings::class);
            $adminEmail  = $appSettings->company_email;
            $adminPhone  = $appSettings->company_phone ?? null;

            // ── 1. Email alert ──────────────────────────────────────────────
            if ($adminEmail) {
                try {
                    (new AnonymousNotifiable)
                        ->route('mail', $adminEmail)
                        ->notify(new PaxCapacityAlertNotification(
                            date:      $date,
                            used:      (int) $used,
                            capacity:  $capacity,
                            remaining: $remaining,
                            threshold: $threshold,
                        ));
                } catch (\Exception $e) {
                    Log::error("PaxAlert: failed to send email for [{$date->toDateString()}]: " . $e->getMessage());
                }
            }

            // ── 2. WhatsApp alert ───────────────────────────────────────────
            /** @var WhatsAppSettings $wa */
            $wa = app(WhatsAppSettings::class);

            if ($wa->enabled && $wa->account_sid && $wa->auth_token && $wa->from_number && $adminPhone) {
                try {
                    $toPhone = str_starts_with($adminPhone, '+') ? $adminPhone : '+' . $adminPhone;
                    $twilio  = new TwilioClient($wa->account_sid, $wa->auth_token);

                    $urgency = $remaining <= 0
                        ? '🔴 FULLY BOOKED'
                        : ($remaining <= 10 ? '🟠 CRITICALLY LOW' : '🟡 LOW CAPACITY');

                    $message = implode("\n", [
                        "⚠️ *PAX Capacity Alert — {$appSettings->company_name}*",
                        "",
                        "{$urgency}",
                        "",
                        "📅 Date      : {$date->format('d/m/Y')}",
                        "👥 Capacity  : {$capacity} PAX",
                        "✅ Used      : {$used} PAX",
                        "⚡ Remaining : {$remaining} PAX",
                        "",
                        "Review bookings before accepting new reservations.",
                    ]);

                    $twilio->messages->create("whatsapp:{$toPhone}", [
                        'from' => 'whatsapp:' . $wa->from_number,
                        'body' => $message,
                    ]);

                    Log::info("PaxAlert: WhatsApp sent for [{$date->toDateString()}] — {$remaining} remaining");
                } catch (\Exception $e) {
                    Log::error("PaxAlert: WhatsApp failed for [{$date->toDateString()}]: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            // Never crash booking creation due to alert failure
            Log::error("PaxAlert: Unexpected error for [{$date->toDateString()}]: " . $e->getMessage());
        }
    }
}
