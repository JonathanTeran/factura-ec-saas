<?php

namespace App\Notifications;

use App\Models\Tenant\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Certificate $certificate,
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
            ->subject("{$urgency}Tu certificado de firma electrónica vence en {$this->daysUntilExpiry} días")
            ->greeting("Hola {$notifiable->name},")
            ->line("El certificado de firma electrónica de {$this->certificate->company->business_name} vence en {$this->daysUntilExpiry} días.")
            ->line("Fecha de vencimiento: {$this->certificate->expires_at->format('d/m/Y')}")
            ->line("Propietario: {$this->certificate->owner_name}")
            ->line('Es importante renovar tu certificado antes de que expire para poder seguir emitiendo documentos electrónicos.')
            ->action('Gestionar Certificados', url("/panel/settings/certificates"))
            ->line('Te recomendamos contactar al BCE o a tu proveedor de certificados para renovarlo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'certificate_expiring',
            'certificate_id' => $this->certificate->id,
            'company_name' => $this->certificate->company->business_name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->certificate->expires_at->toIso8601String(),
            'message' => "Tu certificado vence en {$this->daysUntilExpiry} días",
        ];
    }
}
