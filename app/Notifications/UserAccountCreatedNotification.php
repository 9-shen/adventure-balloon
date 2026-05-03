<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $userName,
        public readonly string $loginEmail,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Adventure Balloon Account is Ready')
            ->greeting('Hello ' . $this->userName . ',')
            ->line('Your account has been created successfully.')
            ->line('You can now log in to the portal using your email address.')
            ->line('**Login Email:** ' . $this->loginEmail)
            ->action('Login to Portal', url('/'))
            ->line('If you did not receive a password, please contact your administrator.')
            ->line('Thank you for working with us!');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
