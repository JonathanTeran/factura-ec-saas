<?php

namespace App\Notifications;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a la administración cuando un cliente cancela su suscripción.
 * Seguro para envío a correos sueltos (AnonymousNotifiable): no usa ->name.
 */
class SubscriptionCancelledAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public ?string $reason = null,
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
        $tenant = $this->subscription->tenant;
        $plan = $this->subscription->plan;

        $mail = (new MailMessage)
            ->subject('Suscripción cancelada — ' . ($tenant?->name ?? 'Cliente'))
            ->greeting('Cancelación de suscripción')
            ->line('Un cliente canceló su suscripción:')
            ->line('Cliente / Cuenta: ' . ($tenant?->name ?? '—'))
            ->line('Plan: ' . ($plan?->name ?? '—'))
            ->line('Motivo: ' . ($this->reason ?: 'No especificado'))
            ->line('Fecha: ' . now()->format('d/m/Y H:i'));

        if ($this->subscription->ends_at) {
            $mail->line('Mantiene acceso hasta: ' . $this->subscription->ends_at->format('d/m/Y'));
        }

        return $mail->line('Revisa el panel de administración para más detalles.');
    }
}
