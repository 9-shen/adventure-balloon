<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverAccountCreatedNotification extends Notification
{
    use Queueable;

    public $driverName;
    public $loginEmail;
    public $rawPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct($driverName, $loginEmail, $rawPassword)
    {
        $this->driverName = $driverName;
        $this->loginEmail = $loginEmail;
        $this->rawPassword = $rawPassword;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Booklix Driver Account is Ready')
            ->greeting('Hello ' . $this->driverName . ',')
            ->line('Your driver account has been created successfully.')
            ->line('You can now log in to the Driver Portal to view your dispatches and schedule.')
            ->line('**Login Email:** ' . $this->loginEmail)
            ->line('**Password:** ' . $this->rawPassword)
            ->action('Access Driver Portal', url('/driver'))
            ->line('Please consider changing your password after your first login.')
            ->line('Thank you for driving with us!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
