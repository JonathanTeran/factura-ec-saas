<?php

namespace App\Notifications;

use App\Models\Billing\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovedNotification extends Notification implements ShouldQueue
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
        $planName = $this->payment->subscription?->plan?->name ?? 'N/A';

        return (new MailMessage)
            ->subject('Pago aprobado - Tu suscripcion ha sido activada')
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu pago por transferencia bancaria ha sido verificado y aprobado.")
            ->line("Plan: {$planName}")
            ->line("Monto: \${$this->payment->total_amount} {$this->payment->currency}")
            ->line("Referencia: {$this->payment->transaction_id}")
            ->line('Tu suscripcion ya se encuentra activa.')
            ->action('Ir al Panel', url('/panel'))
            ->line('Gracias por tu confianza.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_approved',
            'payment_id' => $this->payment->id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => $this->payment->total_amount,
            'message' => 'Tu pago ha sido aprobado',
        ];
    }
}
