<?php

namespace App\Notifications;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? 'N/A';
        $endsAt = $this->subscription->ends_at?->format('d/m/Y') ?? 'N/A';

        return (new MailMessage)
            ->subject("Suscripcion cancelada - Plan {$planName}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu suscripcion al plan {$planName} ha sido cancelada.")
            ->line("Podras seguir usando el servicio hasta el {$endsAt}.")
            ->line("Motivo: " . ($this->subscription->cancellation_reason ?? 'No especificado'))
            ->line('Si deseas reactivar tu suscripcion, puedes hacerlo desde el panel de facturacion.')
            ->action('Ir a Facturacion', url('/panel/settings/billing'))
            ->line('Gracias por haber sido parte de nuestro servicio.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_cancelled',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan?->name,
            'ends_at' => $this->subscription->ends_at?->toIso8601String(),
            'cancellation_reason' => $this->subscription->cancellation_reason,
            'message' => "Suscripcion al plan {$this->subscription->plan?->name} cancelada",
        ];
    }
}
