<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $temporaryPassword = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenido a ' . config('app.name'))
            ->view('emails.welcome', [
                'user' => $notifiable,
                'tenant' => $notifiable->tenant,
                'temporaryPassword' => $this->temporaryPassword,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Bienvenido a AmePhia Facturacion',
        ];
    }
}
