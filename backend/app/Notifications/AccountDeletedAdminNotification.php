<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a la administración cuando un usuario elimina su cuenta.
 * Seguro para envío a correos sueltos (AnonymousNotifiable): no usa ->name.
 */
class AccountDeletedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $userName,
        public string $userEmail,
        public ?string $tenantName = null,
    ) {
        $this->afterCommit = true;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cuenta eliminada — ' . ($this->tenantName ?? $this->userName))
            ->greeting('Eliminación de cuenta')
            ->line('Un usuario eliminó su cuenta desde la app:')
            ->line('Nombre: ' . $this->userName)
            ->line('Correo: ' . $this->userEmail)
            ->line('Cuenta / Empresa: ' . ($this->tenantName ?? '—'))
            ->line('Fecha: ' . now()->format('d/m/Y H:i'))
            ->line('Los comprobantes fiscales quedan conservados por retención legal.');
    }
}
