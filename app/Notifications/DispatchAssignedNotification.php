<?php

namespace App\Notifications;

use App\Models\Dispatch;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DispatchAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Dispatch $dispatch
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dispatch  = $this->dispatch;
        $booking   = $dispatch->booking;
        $appName   = app(AppSettings::class)->company_name;

        // ── Schedule fields ────────────────────────────────────────────────────
        $flightDate  = $dispatch->flight_date
            ? Carbon::parse($dispatch->flight_date)->format('d/m/Y')
            : ($booking?->flight_date?->format('d/m/Y') ?? 'TBC');

        $pickupTime  = $dispatch->pickup_time
            ? substr($dispatch->pickup_time, 0, 5)
            : 'TBC';

        $pickupLoc   = $dispatch->pickup_location  ?? 'TBC';
        $dropoffLoc  = $dispatch->dropoff_location ?? 'TBC';

        // ── Passenger list ─────────────────────────────────────────────────────
        $totalPax    = $dispatch->total_pax ?? $booking?->getTotalPax() ?? '?';
        $primaryPax  = null;
        $paxLines    = 'No passenger records found.';

        if ($booking && $booking->customers->isNotEmpty()) {
            $primaryPax = $booking->customers->firstWhere('is_primary', true)
                        ?? $booking->customers->first();

            $paxLines = $booking->customers->map(function ($c) {
                $star = $c->is_primary ? ' ⭐ Primary' : '';
                $line = "• {$c->full_name} ({$c->type}){$star}";
                if ($c->phone) $line .= " — {$c->phone}";
                if ($c->email) $line .= " | {$c->email}";
                return $line;
            })->implode("\n");
        }

        $primaryContact = $primaryPax
            ? "{$primaryPax->full_name}" . ($primaryPax->phone ? " — {$primaryPax->phone}" : '')
            : 'Not specified';

        // ── Driver assignments ─────────────────────────────────────────────────
        $rows        = $dispatch->dispatchDriverRows->load(['driver', 'vehicle']);
        $driverLines = $rows->map(fn ($row) => sprintf(
            "• %s — %s %s (Plate: %s) — %d PAX assigned",
            $row->driver?->name ?? 'N/A',
            $row->vehicle?->make ?? '',
            $row->vehicle?->model ?? '',
            $row->vehicle?->plate_number ?? '—',
            $row->pax_assigned,
        ))->implode("\n");

        // ── Build the mail ─────────────────────────────────────────────────────
        return (new MailMessage)
            ->subject("Dispatch Assignment — {$dispatch->dispatch_ref} | {$appName}")
            ->greeting("Hello {$notifiable->company_name},")
            ->line("A new dispatch has been assigned to your company. Please review the details below and confirm receipt.")
            ->line('')
            ->line('---')
            ->line('**📋 REFERENCES**')
            ->line("**Dispatch Ref :** {$dispatch->dispatch_ref}")
            ->line("**Booking Ref  :** " . ($booking?->booking_ref ?? 'N/A'))
            ->line('')
            ->line('**📅 SCHEDULE**')
            ->line("**Date          :** {$flightDate}")
            ->line("**Pickup Time   :** {$pickupTime}")
            ->line("**Pickup        :** {$pickupLoc}")
            ->line("**Dropoff       :** {$dropoffLoc}")
            ->line('')
            ->line("**👥 PASSENGERS — Total: {$totalPax}**")
            ->line("**Primary Contact :** {$primaryContact}")
            ->line($paxLines)
            ->line('')
            ->line('**🚗 DRIVER ASSIGNMENTS**')
            ->line($driverLines ?: 'No drivers assigned yet.')
            ->line('')
            ->line('---')
            ->line("Please ensure all drivers are briefed and vehicles are ready before the pickup time.")
            ->salutation("— {$appName} Operations Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dispatch_ref' => $this->dispatch->dispatch_ref,
            'booking_ref'  => $this->dispatch->booking?->booking_ref,
            'flight_date'  => $this->dispatch->flight_date
                ? \Carbon\Carbon::parse($this->dispatch->flight_date)->format('Y-m-d')
                : null,
        ];
    }
}
