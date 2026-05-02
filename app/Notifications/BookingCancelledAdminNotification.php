<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the company/admin email when a booking is cancelled.
 */
class BookingCancelledAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly string  $reason,
        public readonly ?User   $cancelledBy = null,
    ) {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking       = $this->booking;
        $appName       = app(AppSettings::class)->company_name;
        $cancelledBy   = $this->cancelledBy ? $this->cancelledBy->name : 'System';

        $flightDate = $booking->flight_date
            ? Carbon::parse($booking->flight_date)->format('l, d/m/Y')
            : 'TBC';

        return (new MailMessage)
            ->subject("⚠️ Alert: Booking Cancelled — {$booking->booking_ref}")
            ->greeting("Hello Admin,")
            ->line("An operational user has **cancelled** a booking.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING DETAILS**')
            ->line("**Booking Ref      :** {$booking->booking_ref}")
            ->line("**Flight Date      :** {$flightDate}")
            ->line("**Total PAX        :** " . $booking->getTotalPax())
            ->line('')
            ->line('**❌ CANCELLATION INFO**')
            ->line("**Cancelled By     :** {$cancelledBy}")
            ->line("**Reason           :** {$this->reason}")
            ->line('')
            ->line('---')
            ->salutation("— {$appName} System");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref' => $this->booking->booking_ref,
            'flight_date' => $this->booking->flight_date?->format('Y-m-d'),
            'reason'      => $this->reason,
            'cancelled_by'=> $this->cancelledBy?->name,
        ];
    }
}
