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
 * Sent to the partner's email when their booking is confirmed
 * by an admin, manager, or super_admin.
 */
class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly string  $confirmedByName,
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
            ? Carbon::parse($booking->flight_date)->format('l, d/m/Y')
            : 'TBC';

        $flightTime = $booking->flight_time
            ? substr($booking->flight_time, 0, 5)
            : 'TBC';

        $totalPax  = $booking->getTotalPax();
        $adultPax  = $booking->adult_pax;
        $childPax  = $booking->child_pax;
        $amount    = number_format((float) $booking->final_amount, 2);
        $balance   = number_format((float) $booking->balance_due, 2);

        return (new MailMessage)
            ->subject("✅ Booking Confirmed: {$booking->booking_ref}")
            ->greeting("Great news, " . ($partner?->company_name ?? 'Partner') . "!")
            ->line("Your booking has been **confirmed** by our team. Please review the details below and prepare your passengers accordingly.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING CONFIRMATION**')
            ->line("**Booking Ref      :** {$booking->booking_ref}")
            ->line("**Status           :** ✅ Confirmed")
            ->line("**Confirmed By     :** {$this->confirmedByName}")
            ->line('')
            ->line('**📅 FLIGHT DETAILS**')
            ->line("**Flight Date      :** {$flightDate}")
            ->line("**Flight Time      :** {$flightTime}")
            ->line("**Product          :** " . ($product?->name ?? '—'))
            ->line('')
            ->line('**👥 PASSENGERS**')
            ->line("**Adults           :** {$adultPax}")
            ->line("**Children         :** {$childPax}")
            ->line("**Total PAX        :** {$totalPax}")
            ->line('')
            ->line('**💰 FINANCIALS**')
            ->line("**Total Amount     :** {$amount} MAD")
            ->line("**Balance Due      :** {$balance} MAD")
            ->line("**Payment Status   :** " . ucfirst(str_replace('_', ' ', $booking->payment_status ?? 'due')))
            ->line('')
            ->line('---')
            ->line('If you have any questions, please contact us directly.')
            ->salutation("— {$appName} Booking Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref'    => $this->booking->booking_ref,
            'partner'        => $this->booking->partner?->company_name,
            'flight_date'    => $this->booking->flight_date?->format('Y-m-d'),
            'confirmed_by'   => $this->confirmedByName,
        ];
    }
}
