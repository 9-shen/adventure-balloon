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
 * Sent to the assigned Guide when a booking is confirmed.
 */
class BookingConfirmedGuideNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
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
        $product    = $booking->product;
        $appName    = app(AppSettings::class)->company_name;

        $flightDate = $booking->flight_date
            ? Carbon::parse($booking->flight_date)->format('l, d/m/Y')
            : 'TBC';

        $flightTime = $booking->flight_time
            ? substr($booking->flight_time, 0, 5)
            : 'TBC';

        $totalPax  = $booking->getTotalPax();

        return (new MailMessage)
            ->subject("✅ You have been assigned to a new Booking: {$booking->booking_ref}")
            ->greeting("Hello " . ($notifiable->name ?? 'Guide') . ",")
            ->line("You have been assigned to a confirmed booking. Please review the flight details below.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING DETAILS**')
            ->line("**Booking Ref      :** {$booking->booking_ref}")
            ->line("**Status           :** ✅ Confirmed")
            ->line('')
            ->line('**📅 FLIGHT DETAILS**')
            ->line("**Flight Date      :** {$flightDate}")
            ->line("**Flight Time      :** {$flightTime}")
            ->line("**Product          :** " . ($product?->name ?? '—'))
            ->line('')
            ->line('**👥 PASSENGERS**')
            ->line("**Total PAX        :** {$totalPax}")
            ->line('')
            ->line('---')
            ->line('If you have any questions, please contact the dispatch team.')
            ->salutation("— {$appName} Operations");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref' => $this->booking->booking_ref,
            'flight_date' => $this->booking->flight_date?->format('Y-m-d'),
        ];
    }
}
