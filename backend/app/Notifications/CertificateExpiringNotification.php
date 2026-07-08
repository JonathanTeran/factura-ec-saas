<?php

namespace App\Notifications;

use App\Models\Tenant\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de vencimiento de la firma electrónica (.p12). La firma vive en la
 * empresa (Company::signature_*), no en un modelo Certificate propio — el job
 * fallaba a diario con "Class App\Models\Tenant\Certificate not found".
 */
class CertificateExpiringNotification extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject("{$urgency}Tu firma electrónica vence en {$this->daysUntilExpiry} días")
            ->greeting("Hola {$notifiable->name},")
            ->line("La firma electrónica (.p12) de {$this->company->business_name} vence en {$this->daysUntilExpiry} días.")
            ->line('Fecha de vencimiento: '.$this->company->signature_expires_at?->format('d/m/Y'));

        if ($this->company->signature_subject) {
            $mail->line("Titular: {$this->company->signature_subject}");
        }

        return $mail
            ->line('Renuévala antes de que expire para poder seguir emitiendo comprobantes electrónicos.')
            ->action('Gestionar firma electrónica', url('/settings/firma'))
            ->line('Puedes renovarla con tu proveedor de certificados (UANATACA, Security Data, BCE, etc.).');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'certificate_expiring',
            'company_id' => $this->company->id,
            'company_name' => $this->company->business_name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->company->signature_expires_at?->toIso8601String(),
            'message' => "Tu firma electrónica vence en {$this->daysUntilExpiry} días",
        ];
    }
}
