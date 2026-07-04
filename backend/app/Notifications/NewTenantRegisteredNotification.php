<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a la administración cada vez que se registra un nuevo usuario/tenant.
 */
class NewTenantRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
        // Encolar solo después de confirmar la transacción de registro.
        // ($afterCommit lo define el trait Queueable; no se redeclara.)
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
        $tenant = $this->user->tenant;

        return (new MailMessage)
            ->subject('Nuevo registro en AmePhia — ' . ($tenant?->name ?? $this->user->name))
            ->greeting('Nuevo registro')
            ->line('Una persona acaba de registrarse en la plataforma:')
            ->line('Nombre: ' . $this->user->name)
            ->line('Correo: ' . $this->user->email)
            ->line('Empresa / Cuenta: ' . ($tenant?->name ?? '—'))
            ->line('Fecha: ' . now()->format('d/m/Y H:i'))
            ->line('Revisa el panel de administración para ver los detalles.');
    }
}
