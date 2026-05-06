<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BalloonDispatcherAccountCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
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
            ->subject('Your Balloon Dispatcher Account is Ready')
            ->greeting('Hello ' . $this->name . ',')
            ->line('Your Balloon Dispatcher account has been created successfully.')
            ->line('You can now log in to the Balloon Dispatcher Portal to manage dispatches and view bookings.')
            ->line('**Login Email:** ' . $this->loginEmail)
            ->line('**Password:** ' . $this->rawPassword)
            ->action('Access Balloon Dispatcher Portal', url('/balloon-dispatcher'))
            ->line('Please change your password after your first login.')
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
