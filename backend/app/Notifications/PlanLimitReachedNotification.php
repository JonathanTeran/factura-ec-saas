<?php

namespace App\Notifications;

use App\Models\Tenant\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanLimitReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $limitType,
        public int $currentUsage,
        public int $maxAllowed
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $limitName = match ($this->limitType) {
            'documents' => 'documentos mensuales',
            'users' => 'usuarios',
            'companies' => 'empresas',
            'emission_points' => 'puntos de emisión',
            default => $this->limitType,
        };

        return (new MailMessage)
            ->subject("Has alcanzado el límite de {$limitName}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Has alcanzado el límite de {$limitName} de tu plan actual.")
            ->line("Uso actual: {$this->currentUsage} de {$this->maxAllowed}")
            ->line('Para continuar sin interrupciones, te recomendamos actualizar tu plan.')
            ->action('Ver Planes', url('/panel/settings/billing'))
            ->line('Si tienes preguntas sobre los planes, estamos aquí para ayudarte.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'plan_limit_reached',
            'tenant_id' => $this->tenant->id,
            'limit_type' => $this->limitType,
            'current_usage' => $this->currentUsage,
            'max_allowed' => $this->maxAllowed,
            'message' => "Has alcanzado el límite de {$this->limitType}",
        ];
    }
}
