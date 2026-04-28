<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Dispatch;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a TransportCompany or Driver when a booking is cancelled.
 * Pass $isDriver = true when notifying individual drivers.
 */
class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking  $booking,
        public readonly Dispatch $dispatch,
        public readonly string   $reason,
        public readonly bool     $isDriver = false,
    ) {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking    = $this->booking;
        $dispatch   = $this->dispatch;
        $appName    = app(AppSettings::class)->company_name;

        $flightDate = $booking->flight_date
            ? Carbon::parse($booking->flight_date)->format('d/m/Y')
            : 'TBC';

        $recipient  = $this->isDriver
            ? "Hello {$notifiable->name},"
            : "Hello {$notifiable->company_name},";

        $subject = "⚠️ Booking Cancelled — {$booking->booking_ref}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting($recipient)
            ->line("We regret to inform you that the following booking has been **cancelled**.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING DETAILS**')
            ->line("**Booking Ref   :** {$booking->booking_ref}")
            ->line("**Dispatch Ref  :** {$dispatch->dispatch_ref}")
            ->line("**Flight Date   :** {$flightDate}")
            ->line("**Flight Time   :** " . ($dispatch->pickup_time ? substr($dispatch->pickup_time, 0, 5) : 'TBC'))
            ->line("**Pickup        :** " . ($dispatch->pickup_location ?? 'TBC'))
            ->line("**Total PAX     :** " . $booking->getTotalPax())
            ->line('')
            ->line('**❌ CANCELLATION**')
            ->line("**Reason        :** {$this->reason}")
            ->line('')
            ->line('---')
            ->line('Please disregard any prior assignment notifications for this booking. No action is required on your end.')
            ->salutation("— {$appName} Operations Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref'  => $this->booking->booking_ref,
            'dispatch_ref' => $this->dispatch->dispatch_ref,
            'flight_date'  => $this->booking->flight_date?->format('Y-m-d'),
            'reason'       => $this->reason,
        ];
    }
}
