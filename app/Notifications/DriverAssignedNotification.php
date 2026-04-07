<?php

namespace App\Notifications;

use App\Models\Dispatch;
use App\Models\DispatchDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Dispatch       $dispatch,
        public readonly DispatchDriver $driverRow
    ) {}

    public function via(object $notifiable): array
    {
        // Email channel — WhatsApp via Twilio can be added in a future phase
        // by adding a custom 'whatsapp' channel here when TWILIO_* env vars are set
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dispatch  = $this->dispatch;
        $row       = $this->driverRow;
        $booking   = $dispatch->booking;
        $vehicle   = $row->vehicle;

        return (new MailMessage)
            ->subject("Your Dispatch Assignment — {$dispatch->dispatch_ref}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned to a transport dispatch.")
            ->line("")
            ->line("**Dispatch Reference:** {$dispatch->dispatch_ref}")
            ->line("**Booking Reference:** {$booking?->booking_ref}")
            ->line("**Flight Date:** " . $dispatch->flight_date?->format('d/m/Y'))
            ->line("**Pickup Time:** " . ($dispatch->pickup_time ?? 'TBC'))
            ->line("**Pickup Location:** " . ($dispatch->pickup_location ?? 'TBC'))
            ->line("**Dropoff Location:** " . ($dispatch->dropoff_location ?? 'TBC'))
            ->line("")
            ->line("**Your Vehicle:** " . ($vehicle ? "{$vehicle->make} {$vehicle->model} — Plate: {$vehicle->plate_number}" : 'TBC'))
            ->line("**Passengers Assigned to You:** {$row->pax_assigned}")
            ->line("")
            ->line("Please arrive at the pickup location on time. Contact the operations team if you have any questions.")
            ->salutation("Booklix Operations Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dispatch_ref' => $this->dispatch->dispatch_ref,
            'pax_assigned' => $this->driverRow->pax_assigned,
            'flight_date'  => $this->dispatch->flight_date?->toDateString(),
        ];
    }
}
