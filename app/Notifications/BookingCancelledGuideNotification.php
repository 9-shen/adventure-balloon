<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the assigned Guide when a booking is cancelled.
 */
class BookingCancelledGuideNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly string  $reason,
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
        $appName    = app(AppSettings::class)->company_name;

        $flightDate = $booking->flight_date
            ? Carbon::parse($booking->flight_date)->format('l, d/m/Y')
            : 'TBC';

        return (new MailMessage)
            ->subject("⚠️ Booking Cancelled: {$booking->booking_ref}")
            ->greeting("Hello " . ($notifiable->name ?? 'Guide') . ",")
            ->line("Please note that a booking you were assigned to has been **cancelled**.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING DETAILS**')
            ->line("**Booking Ref      :** {$booking->booking_ref}")
            ->line("**Flight Date      :** {$flightDate}")
            ->line('')
            ->line('**❌ CANCELLATION**')
            ->line("**Reason           :** {$this->reason}")
            ->line('')
            ->line('---')
            ->line('Please disregard any prior assignments for this booking.')
            ->salutation("— {$appName} Operations");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref' => $this->booking->booking_ref,
            'flight_date' => $this->booking->flight_date?->format('Y-m-d'),
            'reason'      => $this->reason,
        ];
    }
}
