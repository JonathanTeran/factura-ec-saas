<?php

namespace App\Notifications;

use App\Models\Tenant\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaxpayerDataChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, array{old: mixed, new: mixed}> $changes
     */
    public function __construct(
        public Company $company,
        public array $changes
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('El SRI actualizó tus datos tributarios')
            ->greeting("Hola {$notifiable->name},")
            ->line("Detectamos cambios en el registro del SRI para {$this->company->business_name} y actualizamos tu configuración automáticamente:");

        foreach ($this->changes as $field => $change) {
            $label = self::FIELD_LABELS[$field] ?? $field;
            $mail->line("• {$label}: {$this->format($change['old'])} → {$this->format($change['new'])}");
        }

        return $mail
            ->line('Tus próximos comprobantes ya se emitirán con las leyendas correctas para tu nuevo régimen.')
            ->action('Revisar configuración', url('/settings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'taxpayer_data_changed',
            'company_id' => $this->company->id,
            'company_name' => $this->company->business_name,
            'changes' => $this->changes,
            'message' => 'El SRI actualizó tus datos tributarios y ajustamos tu configuración.',
        ];
    }

    private const FIELD_LABELS = [
        'rimpe_type' => 'Régimen RIMPE',
        'obligated_accounting' => 'Obligado a llevar contabilidad',
        'special_taxpayer' => 'Contribuyente especial',
    ];

    private function format(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        return match ($value) {
            'none' => 'General',
            'emprendedor' => 'RIMPE Emprendedor',
            'negocio_popular' => 'RIMPE Negocio Popular',
            default => (string) $value,
        };
    }
}
