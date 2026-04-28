<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\Driver;
use App\Models\DispatchDriver;
use App\Models\Vehicle;
use App\Notifications\DispatchAssignedNotification;
use App\Notifications\DriverAssignedNotification;
use App\Settings\AppSettings;
use App\Settings\WhatsAppSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class DispatchService
{
    // ─── Reference Generator ─────────────────────────────────────────────────

    /**
     * Generate a unique dispatch reference: DSP-YYYY-NNNN
     * Independent sequence per year.
     */
    public function generateRef(): string
    {
        $prefix = 'DSP';
        $year   = now()->year;

        $count = Dispatch::withTrashed()
            ->where('dispatch_ref', 'like', "{$prefix}-{$year}-%")
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $ref      = "{$prefix}-{$year}-{$sequence}";

        // Collision guard
        while (Dispatch::withTrashed()->where('dispatch_ref', $ref)->exists()) {
            $count++;
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $ref      = "{$prefix}-{$year}-{$sequence}";
        }

        return $ref;
    }

    // ─── Driver Auto-Assignment Algorithm ────────────────────────────────────

    /**
     * Suggest driver assignments based on PAX and available vehicles from the
     * selected transport company.
     *
     * Algorithm:
     *   drivers_needed = ceil(total_pax / vehicle_capacity)
     *   For each vehicle: pax_assigned = min(remaining_pax, vehicle_capacity)
     *
     * Returns array of rows ready to populate the Repeater:
     *   [['driver_id' => X, 'vehicle_id' => Y, 'pax_assigned' => Z], ...]
     */
    public function suggestDriverAssignments(int $totalPax, int $transportCompanyId): array
    {
        if ($totalPax <= 0) {
            return [];
        }

        // Get active vehicles for this company, largest capacity first
        $vehicles = Vehicle::where('transport_company_id', $transportCompanyId)
            ->where('is_active', true)
            ->orderByDesc('capacity')
            ->get();

        $assignments = [];
        $remaining   = $totalPax;
        $usedDriverIds = [];

        foreach ($vehicles as $vehicle) {
            if ($remaining <= 0) {
                break;
            }

            // Prefer a driver assigned to this specific vehicle
            $driver = Driver::where('transport_company_id', $transportCompanyId)
                ->where('is_active', true)
                ->whereNotIn('id', $usedDriverIds)
                ->whereHas('vehicles', fn ($q) => $q->where('vehicles.id', $vehicle->id))
                ->first();

            // Fall back to any available active driver from this company
            if (!$driver) {
                $driver = Driver::where('transport_company_id', $transportCompanyId)
                    ->where('is_active', true)
                    ->whereNotIn('id', $usedDriverIds)
                    ->first();
            }

            if (!$driver) {
                continue; // No more drivers available
            }

            $paxForVehicle = min($remaining, $vehicle->capacity);

            $assignments[]   = [
                'driver_id'    => $driver->id,
                'vehicle_id'   => $vehicle->id,
                'pax_assigned' => $paxForVehicle,
            ];

            $usedDriverIds[] = $driver->id;
            $remaining      -= $paxForVehicle;
        }

        return $assignments;
    }

    // ─── Create Dispatch ─────────────────────────────────────────────────────

    /**
     * Create a dispatch + its dispatch_driver rows in a single transaction.
     * $data must include a 'dispatch_drivers' key with the repeater rows.
     */
    public function createDispatch(array $data): Dispatch
    {
        $driverRows = $data['dispatch_drivers'] ?? [];
        unset($data['dispatch_drivers']);

        return DB::transaction(function () use ($data, $driverRows) {
            /** @var Dispatch $dispatch */
            $dispatch = Dispatch::create($data);

            foreach ($driverRows as $row) {
                $dispatch->dispatchDriverRows()->create([
                    'driver_id'    => $row['driver_id'],
                    'vehicle_id'   => $row['vehicle_id'],
                    'pax_assigned' => (int) ($row['pax_assigned'] ?? 0),
                    'status'       => 'pending',
                ]);
            }

            // Auto-calculate transport cost from vehicle prices
            $dispatch->update([
                'transport_cost' => $dispatch->calculateCost(),
            ]);

            return $dispatch;
        });
    }

    /**
     * Recalculate the transport cost for a dispatch (e.g. after editing driver/vehicle assignments).
     */
    public function recalculateCost(Dispatch $dispatch): Dispatch
    {
        $dispatch->update([
            'transport_cost' => $dispatch->calculateCost(),
        ]);

        return $dispatch->fresh();
    }

    // ─── Notifications ───────────────────────────────────────────────────────

    /**
     * Send the dispatch manifest email to the transport company.
     * Marks notified_at on the dispatch.
     */
    public function notifyTransporter(Dispatch $dispatch): void
    {
        $dispatch->load([
            'transportCompany',
            'booking.product',
            'dispatchDriverRows.driver',
            'dispatchDriverRows.vehicle',
        ]);

        if ($dispatch->transportCompany?->email) {
            try {
                $dispatch->transportCompany->notify(
                    new DispatchAssignedNotification($dispatch)
                );
            } catch (\Exception $e) {
                Log::error("DispatchService: failed to notify transporter [{$dispatch->dispatch_ref}]: " . $e->getMessage());
            }
        }

        $dispatch->update(['notified_at' => now()]);
    }

    /**
     * Send per-driver email notifications only.
     * @deprecated Use notifyAllDrivers() instead — it sends both email + WhatsApp.
     */
    public function notifyDrivers(Dispatch $dispatch): void
    {
        $this->notifyAllDrivers($dispatch);
    }

    /**
     * ── Phase 26-B: Unified driver notification ────────────────────────────────
     * Send BOTH email AND WhatsApp to every assigned driver in a single call.
     * Marks whatsapp_sent + whatsapp_sent_at on each DispatchDriver row.
     *
     * Returns summary: ['email_sent' => N, 'whatsapp_sent' => N, 'failed' => N, 'errors' => [...]]
     */
    public function notifyAllDrivers(Dispatch $dispatch): array
    {
        $dispatch->load([
            'dispatchDriverRows.driver',
            'dispatchDriverRows.vehicle',
            'booking.customers',
            'booking.partner',
        ]);

        /** @var \App\Settings\WhatsAppSettings $wa */
        $wa = app(WhatsAppSettings::class);
        $waEnabled = $wa->enabled && $wa->account_sid && $wa->auth_token && $wa->from_number;

        $twilio = $waEnabled ? new TwilioClient($wa->account_sid, $wa->auth_token) : null;
        $from   = $waEnabled ? 'whatsapp:' . $wa->from_number : null;

        /** @var AppSettings $app */
        $app     = app(AppSettings::class);
        $booking = $dispatch->booking;

        // ── Build shared WhatsApp message parts ───────────────────────────────
        $flightDate = $dispatch->flight_date
            ? Carbon::parse($dispatch->flight_date)->format('d/m/Y')
            : ($booking?->flight_date?->format('d/m/Y') ?? 'TBC');

        $pickupTime = $dispatch->pickup_time
            ? substr($dispatch->pickup_time, 0, 5)
            : 'TBC';

        $pickupLoc  = $dispatch->pickup_location  ?? 'TBC';
        $dropoffLoc = $dispatch->dropoff_location ?? 'TBC';

        $primaryPax = null;
        if ($booking) {
            $primaryPax = $booking->customers->firstWhere('is_primary', true)
                       ?? $booking->customers->first();
        }
        $paxContact = $primaryPax
            ? "{$primaryPax->full_name}" . ($primaryPax->phone ? " — {$primaryPax->phone}" : '')
            : 'Not specified';

        $customerLines = '';
        if ($booking && $booking->customers->isNotEmpty()) {
            $customerLines = "\n";
            foreach ($booking->customers as $c) {
                $tag = $c->is_primary ? ' ⭐' : '';
                $customerLines .= "  • {$c->full_name} ({$c->type}){$tag}";
                if ($c->phone) $customerLines .= " — {$c->phone}";
                $customerLines .= "\n";
            }
        }

        $results = ['email_sent' => 0, 'whatsapp_sent' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($dispatch->dispatchDriverRows as $row) {
            $driver = $row->driver;

            if (!$driver) {
                $results['skipped']++;
                continue;
            }

            // ── Email ──────────────────────────────────────────────────────────
            if ($driver->email) {
                try {
                    $driver->notify(new DriverAssignedNotification($dispatch, $row));
                    $results['email_sent']++;
                } catch (\Exception $e) {
                    Log::error("DispatchService: email failed for driver [{$driver->id}] [{$dispatch->dispatch_ref}]: " . $e->getMessage());
                    $results['errors'][] = "Email to {$driver->name}: " . $e->getMessage();
                }
            }

            // ── WhatsApp ───────────────────────────────────────────────────────
            if ($waEnabled && $driver->phone) {
                $rawPhone = $driver->phone;
                $toPhone  = str_starts_with($rawPhone, '+') ? $rawPhone : '+' . $rawPhone;
                $to       = 'whatsapp:' . $toPhone;

                $vehicle     = $row->vehicle;
                $vehicleInfo = $vehicle
                    ? "{$vehicle->make} {$vehicle->model} — Plate: {$vehicle->plate_number}"
                    : 'TBC';

                $waMessage = implode("\n", [
                    "🚐 *{$app->company_name} — Dispatch Assignment*",
                    "",
                    "Hello {$driver->name},",
                    "You have been assigned to a dispatch. Please review the details below.",
                    "",
                    "📋 *References*",
                    "  Dispatch Ref : {$dispatch->dispatch_ref}",
                    "  Booking Ref  : " . ($booking?->booking_ref ?? 'N/A'),
                    "",
                    "📅 *Schedule*",
                    "  Date         : {$flightDate}",
                    "  Pickup Time  : {$pickupTime}",
                    "  Pickup       : {$pickupLoc}",
                    "  Dropoff      : {$dropoffLoc}",
                    "",
                    "👥 *Passengers ({$row->pax_assigned} assigned to you)*",
                    "  Total PAX    : " . ($booking?->getTotalPax() ?? '?'),
                    "  Primary CTX  : {$paxContact}",
                    $customerLines,
                    "🚗 *Your Vehicle*",
                    "  {$vehicleInfo}",
                    "",
                    "Please be punctual. Contact us if you have any issues.",
                    "— {$app->company_name} Operations",
                ]);

                try {
                    $twilio->messages->create($to, [
                        'from' => $from,
                        'body' => $waMessage,
                    ]);

                    $row->update([
                        'whatsapp_sent'    => true,
                        'whatsapp_sent_at' => now(),
                    ]);

                    $results['whatsapp_sent']++;
                    Log::info("WhatsApp sent to driver [{$driver->name}] for dispatch [{$dispatch->dispatch_ref}]");
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "WhatsApp to {$driver->name}: " . $e->getMessage();
                    Log::error("WhatsApp failed for driver [{$driver->name}] [{$dispatch->dispatch_ref}]: " . $e->getMessage());
                }
            } elseif (!$waEnabled) {
                // WhatsApp disabled globally — still mark row if email was sent
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    // ── Phase 26-C: Cancellation WhatsApp ─────────────────────────────────────

    /**
     * Send a WhatsApp cancellation alert to all drivers assigned to this dispatch.
     * Called from the Cancel Booking action AFTER the booking is marked cancelled.
     *
     * Returns ['sent' => N, 'failed' => N, 'skipped' => N, 'errors' => [...]]
     */
    public function sendCancellationWhatsApp(Dispatch $dispatch, string $reason): array
    {
        /** @var WhatsAppSettings $wa */
        $wa = app(WhatsAppSettings::class);

        if (! $wa->enabled || ! $wa->account_sid || ! $wa->auth_token || ! $wa->from_number) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => ['WhatsApp disabled or Twilio credentials missing.']];
        }

        /** @var AppSettings $app */
        $app = app(AppSettings::class);

        $dispatch->load([
            'booking',
            'dispatchDriverRows.driver',
        ]);

        $booking    = $dispatch->booking;
        $flightDate = $dispatch->flight_date
            ? Carbon::parse($dispatch->flight_date)->format('d/m/Y')
            : ($booking?->flight_date?->format('d/m/Y') ?? 'TBC');

        $twilio  = new TwilioClient($wa->account_sid, $wa->auth_token);
        $from    = 'whatsapp:' . $wa->from_number;
        $results = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($dispatch->dispatchDriverRows as $row) {
            $driver = $row->driver;

            if (! $driver || ! $driver->phone) {
                $results['skipped']++;
                continue;
            }

            $toPhone = str_starts_with($driver->phone, '+') ? $driver->phone : '+' . $driver->phone;
            $to      = 'whatsapp:' . $toPhone;

            $message = implode("\n", [
                "❌ *{$app->company_name} — Booking CANCELLED*",
                "",
                "Hello {$driver->name},",
                "The following booking has been cancelled. No action is required.",
                "",
                "📋 *Details*",
                "  Booking Ref  : " . ($booking?->booking_ref ?? 'N/A'),
                "  Dispatch Ref : {$dispatch->dispatch_ref}",
                "  Flight Date  : {$flightDate}",
                "  Pickup       : " . ($dispatch->pickup_location ?? 'TBC'),
                "",
                "❌ *Cancellation Reason*",
                "  {$reason}",
                "",
                "Please disregard your previous assignment notification.",
                "— {$app->company_name} Operations",
            ]);

            try {
                $twilio->messages->create($to, [
                    'from' => $from,
                    'body' => $message,
                ]);
                $results['sent']++;
                Log::info("Cancellation WhatsApp sent to driver [{$driver->name}] for [{$dispatch->dispatch_ref}]");
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Driver {$driver->name}: " . $e->getMessage();
                Log::error("Cancellation WhatsApp failed for driver [{$driver->name}]: " . $e->getMessage());
            }
        }

        return $results;
    }

    // ─── WhatsApp: Send to All Assigned Drivers ───────────────────────────────

    /**
     * Send a WhatsApp message to every assigned driver for this dispatch.
     * Uses Twilio creds from WhatsAppSettings (DB-stored via Spatie Settings).
     * Marks whatsapp_sent + whatsapp_sent_at on each DispatchDriver row.
     *
     * Returns ['sent' => N, 'failed' => N, 'skipped' => N, 'errors' => [...]]
     */
    public function sendWhatsAppToDrivers(Dispatch $dispatch): array
    {
        /** @var WhatsAppSettings $wa */
        $wa = app(WhatsAppSettings::class);

        if (! $wa->enabled || ! $wa->account_sid || ! $wa->auth_token || ! $wa->from_number) {
            return [
                'sent'    => 0,
                'failed'  => 0,
                'skipped' => 0,
                'errors'  => ['WhatsApp is disabled or Twilio credentials are missing.'],
            ];
        }

        /** @var AppSettings $app */
        $app = app(AppSettings::class);

        $dispatch->load([
            'booking.product',
            'booking.customers',
            'booking.partner',
            'dispatchDriverRows.driver',
            'dispatchDriverRows.vehicle',
            'transportCompany',
        ]);

        $booking = $dispatch->booking;

        // ── Build shared message parts ────────────────────────────────────────
        $flightDate   = $dispatch->flight_date
            ? Carbon::parse($dispatch->flight_date)->format('d/m/Y')
            : ($booking?->flight_date?->format('d/m/Y') ?? 'TBC');

        $pickupTime   = $dispatch->pickup_time
            ? substr($dispatch->pickup_time, 0, 5)   // HH:MM
            : 'TBC';

        $pickupLoc    = $dispatch->pickup_location  ?? 'TBC';
        $dropoffLoc   = $dispatch->dropoff_location ?? 'TBC';

        // Primary passenger contact (first customer marked is_primary, or first row)
        $primaryPax   = null;
        if ($booking) {
            $primaryPax = $booking->customers->firstWhere('is_primary', true)
                       ?? $booking->customers->first();
        }
        $paxContact = $primaryPax
            ? "{$primaryPax->full_name}" . ($primaryPax->phone ? " — {$primaryPax->phone}" : '')
            : 'Not specified';

        // All customers brief list
        $customerLines = '';
        if ($booking && $booking->customers->isNotEmpty()) {
            $customerLines = "\n";
            foreach ($booking->customers as $c) {
                $tag = $c->is_primary ? ' ⭐' : '';
                $customerLines .= "  • {$c->full_name} ({$c->type}){$tag}";
                if ($c->phone) $customerLines .= " — {$c->phone}";
                $customerLines .= "\n";
            }
        }

        $results = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];

        $twilio = new TwilioClient($wa->account_sid, $wa->auth_token);
        $from   = 'whatsapp:' . $wa->from_number;

        foreach ($dispatch->dispatchDriverRows as $row) {
            $driver = $row->driver;

            if (! $driver || ! $driver->phone) {
                $results['skipped']++;
                continue;
            }

            // Normalise phone: ensure + prefix
            $rawPhone = $driver->phone;
            $toPhone  = str_starts_with($rawPhone, '+') ? $rawPhone : '+' . $rawPhone;
            $to       = 'whatsapp:' . $toPhone;

            $vehicle = $row->vehicle;
            $vehicleInfo = $vehicle
                ? "{$vehicle->make} {$vehicle->model} — Plate: {$vehicle->plate_number}"
                : 'TBC';

            $message = implode("\n", [
                "🚐 *{$app->company_name} — Dispatch Assignment*",
                "",
                "Hello {$driver->name},",
                "You have been assigned to a dispatch. Please review the details below.",
                "",
                "📋 *References*",
                "  Dispatch Ref : {$dispatch->dispatch_ref}",
                "  Booking Ref  : " . ($booking?->booking_ref ?? 'N/A'),
                "",
                "📅 *Schedule*",
                "  Date         : {$flightDate}",
                "  Pickup Time  : {$pickupTime}",
                "  Pickup       : {$pickupLoc}",
                "  Dropoff      : {$dropoffLoc}",
                "",
                "👥 *Passengers ({$row->pax_assigned} assigned to you)*",
                "  Total PAX    : " . ($booking?->getTotalPax() ?? '?'),
                "  Primary CTX  : {$paxContact}",
                $customerLines,
                "🚗 *Your Vehicle*",
                "  {$vehicleInfo}",
                "",
                "Please be punctual. Contact us if you have any issues.",
                "— {$app->company_name} Operations",
            ]);

            try {
                $twilio->messages->create($to, [
                    'from' => $from,
                    'body' => $message,
                ]);

                $row->update([
                    'whatsapp_sent'    => true,
                    'whatsapp_sent_at' => now(),
                ]);

                $results['sent']++;

                Log::info("WhatsApp sent to driver [{$driver->name}] for dispatch [{$dispatch->dispatch_ref}]");

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Driver {$driver->name}: " . $e->getMessage();
                Log::error("WhatsApp failed for driver [{$driver->name}] [{$dispatch->dispatch_ref}]: " . $e->getMessage());
            }
        }

        return $results;
    }
}
