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
 * Sent to the Partner (via AnonymousNotifiable → partner->email)
 * when a partner-type booking is cancelled by admin/manager.
 */
class PartnerBookingCancelledNotification extends Notification implements ShouldQueue
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
        $partner    = $booking->partner;
        $product    = $booking->product;
        $appName    = app(AppSettings::class)->company_name;

        $flightDate = $booking->flight_date
            ? Carbon::parse($booking->flight_date)->format('d/m/Y')
            : 'TBC';

        $totalPax  = $booking->getTotalPax();
        $amount    = number_format((float) $booking->final_amount, 2);

        return (new MailMessage)
            ->subject("⚠️ Booking Cancelled — {$booking->booking_ref}")
            ->greeting("Dear " . ($partner?->company_name ?? 'Partner') . ",")
            ->line("We regret to inform you that your booking has been **cancelled** by our operations team.")
            ->line('')
            ->line('---')
            ->line('**📋 BOOKING DETAILS**')
            ->line("**Booking Ref  :** {$booking->booking_ref}")
            ->line("**Product      :** " . ($product?->name ?? '—'))
            ->line("**Flight Date  :** {$flightDate}")
            ->line("**Flight Time  :** " . ($booking->flight_time ? substr($booking->flight_time, 0, 5) : 'TBC'))
            ->line('')
            ->line('**👥 PASSENGERS**')
            ->line("**Adults       :** {$booking->adult_pax}")
            ->line("**Children     :** {$booking->child_pax}")
            ->line("**Total PAX    :** {$totalPax}")
            ->line('')
            ->line('**💰 FINANCIALS**')
            ->line("**Total Amount :** {$amount} MAD")
            ->line('')
            ->line('**❌ CANCELLATION REASON**')
            ->line($this->reason)
            ->line('')
            ->line('---')
            ->line('If you have any questions or wish to re-book, please contact our team.')
            ->salutation("— {$appName} Operations Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_ref' => $this->booking->booking_ref,
            'partner'     => $this->booking->partner?->company_name,
            'flight_date' => $this->booking->flight_date?->format('Y-m-d'),
            'reason'      => $this->reason,
        ];
    }
}
