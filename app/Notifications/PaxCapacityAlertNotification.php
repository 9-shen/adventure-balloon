<?php

namespace App\Notifications;

use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the admin (AnonymousNotifiable → company_email)
 * when remaining PAX for a date drops to or below warning_threshold.
 */
class PaxCapacityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Carbon $date,
        public readonly int    $used,
        public readonly int    $capacity,
        public readonly int    $remaining,
        public readonly int    $threshold,
    ) {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName    = app(AppSettings::class)->company_name;
        $dateLabel  = $this->date->format('l, d/m/Y');
        $percentage = $this->capacity > 0
            ? round(($this->used / $this->capacity) * 100)
            : 0;

        $urgency = $this->remaining <= 0
            ? '🔴 FULLY BOOKED'
            : ($this->remaining <= 10 ? '🟠 CRITICALLY LOW' : '🟡 LOW CAPACITY');

        return (new MailMessage)
            ->subject("⚠️ PAX Capacity Alert — {$this->remaining} seats left on {$this->date->format('d/m/Y')}")
            ->greeting("Daily Capacity Warning — {$appName}")
            ->line("**{$urgency}** — The PAX capacity for an upcoming flight date has reached the alert threshold.")
            ->line('')
            ->line('---')
            ->line('**📅 FLIGHT DATE**')
            ->line("**Date      :** {$dateLabel}")
            ->line('')
            ->line('**👥 CAPACITY STATUS**')
            ->line("**Daily Cap :** {$this->capacity} PAX")
            ->line("**Booked    :** {$this->used} PAX ({$percentage}% full)")
            ->line("**Remaining :** {$this->remaining} PAX")
            ->line("**Threshold :** Alert triggers at ≤ {$this->threshold} remaining")
            ->line('')
            ->line('---')
            ->line('Please review the bookings for this date before accepting further reservations.')
            ->salutation("— {$appName} Booking System");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'date'      => $this->date->toDateString(),
            'used'      => $this->used,
            'capacity'  => $this->capacity,
            'remaining' => $this->remaining,
        ];
    }
}
