<?php

namespace App\Notifications;

use App\Models\Portal\CustomerPortalToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPortalMagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CustomerPortalToken $token,
        public string $tenantName,
    ) {
        $this->queue = 'email';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('portal.auth', ['token' => $this->token->token]);

        return (new MailMessage)
            ->subject("Accede a tu portal de documentos - {$this->tenantName}")
            ->greeting('Hola,')
            ->line("Has solicitado acceso a tu portal de documentos electrónicos en **{$this->tenantName}**.")
            ->line('Haz clic en el siguiente botón para acceder:')
            ->action('Acceder al Portal', $url)
            ->line('Este enlace es válido por ' . config('portal.token_expiry_hours', 24) . ' horas y solo puede ser usado una vez.')
            ->line('Si no solicitaste este acceso, puedes ignorar este correo de forma segura.')
            ->salutation("Atentamente,\n{$this->tenantName}");
    }
}
