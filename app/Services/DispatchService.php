<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Notifications\DispatchAssignedNotification;
use App\Notifications\DriverAssignedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            return $dispatch;
        });
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
     * Send per-driver notifications (email; WhatsApp when Twilio configured).
     * Marks whatsapp_sent + whatsapp_sent_at on each dispatch_driver row.
     */
    public function notifyDrivers(Dispatch $dispatch): void
    {
        $dispatch->load([
            'dispatchDriverRows.driver',
            'dispatchDriverRows.vehicle',
            'booking',
        ]);

        foreach ($dispatch->dispatchDriverRows as $row) {
            if (!$row->driver) {
                continue;
            }

            try {
                $row->driver->notify(new DriverAssignedNotification($dispatch, $row));
                $row->update([
                    'whatsapp_sent'    => true,
                    'whatsapp_sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error("DispatchService: failed to notify driver [{$row->driver_id}]: " . $e->getMessage());
            }
        }
    }
}
