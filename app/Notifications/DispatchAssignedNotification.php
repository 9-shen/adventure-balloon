<?php

namespace App\Notifications;

use App\Models\Dispatch;
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
        $dispatch = $this->dispatch;
        $booking  = $dispatch->booking;
        $rows     = $dispatch->dispatchDriverRows->load(['driver', 'vehicle']);

        $driverLines = $rows->map(fn ($row) => sprintf(
            "  • %s — %s %s (cap: %d) — PAX: %d",
            $row->driver?->name ?? 'N/A',
            $row->vehicle?->make ?? '',
            $row->vehicle?->model ?? '',
            $row->vehicle?->capacity ?? 0,
            $row->pax_assigned,
        ))->implode("\n");

        return (new MailMessage)
            ->subject("Dispatch Assignment — {$dispatch->dispatch_ref}")
            ->greeting("Hello {$notifiable->company_name},")
            ->line("A new dispatch has been assigned to your company.")
            ->line("")
            ->line("**Dispatch Reference:** {$dispatch->dispatch_ref}")
            ->line("**Booking Reference:** {$booking?->booking_ref}")
            ->line("**Flight Date:** " . $dispatch->flight_date?->format('d/m/Y'))
            ->line("**Pickup Time:** " . ($dispatch->pickup_time ?? 'TBC'))
            ->line("**Pickup Location:** " . ($dispatch->pickup_location ?? 'TBC'))
            ->line("**Dropoff Location:** " . ($dispatch->dropoff_location ?? 'TBC'))
            ->line("**Total Passengers:** {$dispatch->total_pax}")
            ->line("")
            ->line("**Driver Assignments:**")
            ->line($driverLines ?: 'No drivers assigned yet.')
            ->line("")
            ->line("Please confirm receipt of this dispatch assignment.")
            ->salutation("Booklix Operations Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dispatch_ref' => $this->dispatch->dispatch_ref,
            'booking_ref'  => $this->dispatch->booking?->booking_ref,
            'flight_date'  => $this->dispatch->flight_date?->toDateString(),
        ];
    }
}
