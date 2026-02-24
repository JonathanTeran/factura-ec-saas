<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event,
        public array $data
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubjectForEvent();
        $lines = $this->getLinesForEvent();

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hola {$notifiable->name},");

        foreach ($lines as $line) {
            $mail->line($line);
        }

        if ($url = $this->getActionUrl()) {
            $mail->action('Ver Detalles', $url);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_event',
            'event' => $this->event,
            'data' => $this->data,
            'message' => $this->getSubjectForEvent(),
        ];
    }

    private function getSubjectForEvent(): string
    {
        return match ($this->event) {
            'payment.pending' => 'Nuevo pago pendiente de aprobacion',
            'payment.failed' => 'Pago fallido detectado',
            'subscription.canceled' => 'Suscripcion cancelada',
            'subscription.expired' => 'Suscripcion expirada',
            'tenant.created' => 'Nuevo tenant registrado',
            'certificate.expiring' => 'Certificado proximo a vencer',
            'document.failed' => 'Documento rechazado por el SRI',
            default => "Evento: {$this->event}",
        };
    }

    private function getLinesForEvent(): array
    {
        return match ($this->event) {
            'payment.pending' => [
                "Tenant: " . ($this->data['tenant_name'] ?? 'N/A'),
                "Monto: \$" . ($this->data['amount'] ?? '0.00'),
                "Metodo: Transferencia bancaria",
                "Se requiere verificacion y aprobacion del comprobante.",
            ],
            'payment.failed' => [
                "Tenant: " . ($this->data['tenant_name'] ?? 'N/A'),
                "Monto: \$" . ($this->data['amount'] ?? '0.00'),
                "Error: " . ($this->data['error'] ?? 'Desconocido'),
            ],
            'subscription.canceled' => [
                "Tenant: " . ($this->data['tenant_name'] ?? 'N/A'),
                "Plan: " . ($this->data['plan_name'] ?? 'N/A'),
                "Motivo: " . ($this->data['reason'] ?? 'No especificado'),
            ],
            'subscription.expired' => [
                "Tenant: " . ($this->data['tenant_name'] ?? 'N/A'),
                "Plan: " . ($this->data['plan_name'] ?? 'N/A'),
            ],
            'tenant.created' => [
                "Nombre: " . ($this->data['tenant_name'] ?? 'N/A'),
                "Email: " . ($this->data['email'] ?? 'N/A'),
            ],
            'certificate.expiring' => [
                "Empresa: " . ($this->data['company_name'] ?? 'N/A'),
                "Dias restantes: " . ($this->data['days_remaining'] ?? 'N/A'),
            ],
            'document.failed' => [
                "Documento: " . ($this->data['document_number'] ?? 'N/A'),
                "Tenant: " . ($this->data['tenant_name'] ?? 'N/A'),
                "Error SRI: " . ($this->data['error'] ?? 'Desconocido'),
            ],
            default => [
                "Se ha producido el evento: {$this->event}",
                "Datos: " . json_encode($this->data),
            ],
        };
    }

    private function getActionUrl(): ?string
    {
        return match ($this->event) {
            'payment.pending', 'payment.failed' => isset($this->data['payment_id'])
                ? url("/admin/payments/{$this->data['payment_id']}")
                : url('/admin/payments'),
            'subscription.canceled', 'subscription.expired' => isset($this->data['tenant_id'])
                ? url("/admin/tenants/{$this->data['tenant_id']}")
                : url('/admin/tenants'),
            'tenant.created' => isset($this->data['tenant_id'])
                ? url("/admin/tenants/{$this->data['tenant_id']}")
                : url('/admin/tenants'),
            default => url('/admin'),
        };
    }
}
