<?php

namespace App\Notifications;

use App\Models\Tenant\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Company $company,
        public int $daysUntilExpiry
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysUntilExpiry <= 7 ? 'URGENTE: ' : '';

        return (new MailMessage)
            ->subject("{$urgency}Tu firma electrónica vence en {$this->daysUntilExpiry} días")
            ->greeting("Hola {$notifiable->name},")
            ->line("La firma electrónica de {$this->company->business_name} vence en {$this->daysUntilExpiry} días ({$this->company->signature_expires_at->format('d/m/Y')}).")
            ->line('Sin una firma vigente no podrás emitir comprobantes electrónicos. Renuévala con tu proveedor (Security Data, Uanataca, Registro Civil, etc.) y súbela al sistema.')
            ->action('Actualizar firma electrónica', url('/settings/firma'))
            ->line('Si ya la renovaste, ignora este mensaje.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'signature_expiring',
            'company_id' => $this->company->id,
            'company_name' => $this->company->business_name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->company->signature_expires_at?->toIso8601String(),
            'message' => "Tu firma electrónica vence en {$this->daysUntilExpiry} días",
        ];
    }
}
