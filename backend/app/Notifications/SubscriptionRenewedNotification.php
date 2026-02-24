<?php

namespace App\Notifications;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewedNotification extends Notification implements ShouldQueue
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
        $cycle = $this->subscription->getBillingCycleLabel();

        return (new MailMessage)
            ->subject("Suscripcion renovada - Plan {$planName}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu suscripcion al plan {$planName} ha sido renovada exitosamente.")
            ->line("Ciclo de facturacion: {$cycle}")
            ->line("Monto: \${$this->subscription->amount} {$this->subscription->currency}")
            ->line("Proxima renovacion: {$endsAt}")
            ->action('Ir al Panel', url('/panel'))
            ->line('Gracias por continuar con nosotros.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_renewed',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan?->name,
            'ends_at' => $this->subscription->ends_at?->toIso8601String(),
            'amount' => $this->subscription->amount,
            'message' => "Suscripcion al plan {$this->subscription->plan?->name} renovada",
        ];
    }
}
