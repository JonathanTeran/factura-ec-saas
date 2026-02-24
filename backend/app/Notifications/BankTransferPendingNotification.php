<?php

namespace App\Notifications;

use App\Models\Billing\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankTransferPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = $this->payment->tenant?->name ?? 'N/A';
        $planName = $this->payment->subscription?->plan?->name ?? 'N/A';

        return (new MailMessage)
            ->subject("Transferencia pendiente de aprobacion - {$tenantName}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Se ha registrado un nuevo pago por transferencia bancaria que requiere tu aprobacion.")
            ->line("Tenant: {$tenantName}")
            ->line("Plan: {$planName}")
            ->line("Monto: \${$this->payment->total_amount} {$this->payment->currency}")
            ->line("Referencia: {$this->payment->transfer_reference}")
            ->action('Revisar Pago', url("/admin/payments/{$this->payment->id}"))
            ->line('Por favor verifica el comprobante y aprueba o rechaza el pago.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bank_transfer_pending',
            'payment_id' => $this->payment->id,
            'tenant_name' => $this->payment->tenant?->name,
            'amount' => $this->payment->total_amount,
            'message' => "Transferencia pendiente de {$this->payment->tenant?->name}",
        ];
    }
}
