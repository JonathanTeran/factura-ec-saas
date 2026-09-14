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
            'fef_sync.failed' => 'Sincronización FEF fallida (árbitros)',
            'fef_sync.stale' => 'Sincronización FEF sin corridas correctas',
            'paypal.payment_completed' => 'Pago recibido por PayPal',
            'paypal.review_required' => 'Pago de PayPal requiere revisión',
            'paypal.payment_failed' => 'Pago de PayPal rechazado',
            'paypal.refunded' => 'Reembolso o contracargo en PayPal',
            default => "Evento: {$this->event}",
        };
    }

    private function getLinesForEvent(): array
    {
        return match ($this->event) {
            'payment.pending' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Monto: $'.($this->data['amount'] ?? '0.00'),
                'Metodo: Transferencia bancaria',
                'Se requiere verificacion y aprobacion del comprobante.',
            ],
            'payment.failed' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Monto: $'.($this->data['amount'] ?? '0.00'),
                'Error: '.($this->data['error'] ?? 'Desconocido'),
            ],
            'subscription.canceled' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Plan: '.($this->data['plan_name'] ?? 'N/A'),
                'Motivo: '.($this->data['reason'] ?? 'No especificado'),
            ],
            'subscription.expired' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Plan: '.($this->data['plan_name'] ?? 'N/A'),
            ],
            'tenant.created' => [
                'Nombre: '.($this->data['tenant_name'] ?? 'N/A'),
                'Email: '.($this->data['email'] ?? 'N/A'),
            ],
            'certificate.expiring' => [
                'Empresa: '.($this->data['company_name'] ?? 'N/A'),
                'Dias restantes: '.($this->data['days_remaining'] ?? 'N/A'),
            ],
            'document.failed' => [
                'Documento: '.($this->data['document_number'] ?? 'N/A'),
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Error SRI: '.($this->data['error'] ?? 'Desconocido'),
            ],
            'fef_sync.failed' => [
                'La sincronización con la API de la FEF (campeonatos, clubes, partidos y árbitros) falló.',
                'Origen: '.($this->data['trigger'] ?? 'N/A').' · Inicio: '.($this->data['started_at'] ?? 'N/A'),
                'Error: '.($this->data['error'] ?? 'Desconocido'),
                'Horizon reintentará; si persiste, revisa el historial en Árbitros → Sincronización FEF.',
            ],
            'paypal.payment_completed' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Plan: '.($this->data['plan_name'] ?? 'N/A'),
                'Monto: $'.($this->data['amount'] ?? '0.00').' (IVA incluido)',
                'Pago: '.($this->data['invoice_number'] ?? 'N/A').' · Captura PayPal: '.($this->data['capture_id'] ?? 'N/A'),
                'La suscripción se activó automáticamente; no requiere aprobación.',
            ],
            'paypal.review_required' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Monto esperado: $'.($this->data['amount'] ?? '0.00').(isset($this->data['captured']) ? ' · Cobrado: '.$this->data['captured'] : ''),
                'Motivo: '.($this->data['reason'] ?? 'No especificado'),
                'Orden PayPal: '.($this->data['order_id'] ?? 'N/A').' · Pago: '.($this->data['invoice_number'] ?? 'N/A'),
                'Revisa el cobro en PayPal y activa o reembolsa desde Facturación → Pagos.',
            ],
            'paypal.payment_failed' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Monto: $'.($this->data['amount'] ?? '0.00'),
                'Motivo: '.($this->data['reason'] ?? 'No especificado'),
                'Orden PayPal: '.($this->data['order_id'] ?? 'N/A'),
            ],
            'paypal.refunded' => [
                'Tenant: '.($this->data['tenant_name'] ?? 'N/A'),
                'Tipo: '.($this->data['kind'] ?? 'Reembolso').' · Monto devuelto: $'.($this->data['refunded'] ?? '0.00').' de $'.($this->data['amount'] ?? '0.00'),
                'Pago: '.($this->data['invoice_number'] ?? 'N/A').' · Captura PayPal: '.($this->data['capture_id'] ?? 'N/A'),
                'La suscripción no se cancela sola: decide si corresponde cancelarla.',
            ],
            'fef_sync.stale' => [
                'No hay una sincronización FEF correcta en las últimas '.($this->data['hours'] ?? '?').' horas.',
                'Última correcta: '.($this->data['last_ok_at'] ?? 'nunca').' · Estado de la última corrida: '.($this->data['last_status'] ?? 'N/A'),
                'Los árbitros podrían no recibir propuestas de partidos nuevos hasta que se recupere.',
            ],
            default => [
                "Se ha producido el evento: {$this->event}",
                'Datos: '.json_encode($this->data),
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
            'fef_sync.failed', 'fef_sync.stale' => url('/admin/fef-sync-runs'),
            default => url('/admin'),
        };
    }
}
