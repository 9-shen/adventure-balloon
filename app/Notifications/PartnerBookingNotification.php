<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnerBookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking
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
        $partner    = $booking->partner;
        $product    = $booking->product;
        $appName    = app(AppSettings::class)->company_name;

        $flightDate = $booking->flight_date
            ? Carbon::parse($booking->flight_date)->format('d/m/Y')
            : 'TBC';

        $totalPax  = $booking->getTotalPax();
        $adultPax  = $booking->adult_pax;
        $childPax  = $booking->child_pax;
        $amount    = number_format((float) $booking->final_amount, 2);

        return (new MailMessage)
            ->subject("New Partner Booking: {$booking->booking_ref} — {$partner?->company_name}")
            ->greeting("New Partner Booking Received")
            ->line("A new partner booking has been created in the system. Please review the details below.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING DETAILS**')
            ->line("**Booking Ref  :** {$booking->booking_ref}")
            ->line("**Partner      :** " . ($partner?->company_name ?? '—'))
            ->line("**Product      :** " . ($product?->name ?? '—'))
            ->line('')
            ->line('**📅 SCHEDULE**')
            ->line("**Flight Date  :** {$flightDate}")
            ->line("**Flight Time  :** " . ($booking->flight_time ? substr($booking->flight_time, 0, 5) : 'TBC'))
            ->line('')
            ->line("**👥 PASSENGERS**")
            ->line("**Adults       :** {$adultPax}")
            ->line("**Children     :** {$childPax}")
            ->line("**Total PAX    :** {$totalPax}")
            ->line('')
            ->line('**💰 FINANCIALS**')
            ->line("**Final Amount :** {$amount} MAD")
            ->line("**Payment      :** " . ucfirst(str_replace('_', ' ', $booking->payment_status ?? 'due')))
            ->line('')
            ->line('---')
            ->salutation("— {$appName} Booking System");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref' => $this->booking->booking_ref,
            'partner'     => $this->booking->partner?->company_name,
            'flight_date' => $this->booking->flight_date,
            'total_pax'   => $this->booking->getTotalPax(),
        ];
    }
}
