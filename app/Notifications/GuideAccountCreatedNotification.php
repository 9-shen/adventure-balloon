<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuideAccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $guideName,
        public readonly string $loginEmail,
        public readonly string $rawPassword,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Booklix Guide Account is Ready')
            ->greeting('Hello ' . $this->guideName . ',')
            ->line('Your guide account has been created successfully.')
            ->line('You can now log in to the Guide Portal to view and create bookings on behalf of your agency.')
            ->line('**Login Email:** ' . $this->loginEmail)
            ->line('**Password:** ' . $this->rawPassword)
            ->action('Access Guide Portal', url('/guide'))
            ->line('Please change your password after your first login.')
            ->line('Thank you for working with us!');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
