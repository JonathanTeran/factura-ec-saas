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
        $isPayPal = $this->payment->payment_method === \App\Enums\PaymentMethod::PAYPAL;
        $reference = $this->payment->transaction_id ?: $this->payment->invoice_number;
        $endsAt = $this->payment->subscription?->ends_at?->format('d/m/Y');

        return (new MailMessage)
            ->subject($isPayPal ? 'Pago recibido: tu suscripción está activa' : 'Pago aprobado: tu suscripción está activa')
            ->greeting("Hola {$notifiable->name},")
            ->line($isPayPal
                ? 'Recibimos tu pago con PayPal.'
                : 'Tu pago por transferencia bancaria ha sido verificado y aprobado.')
            ->line("Plan: {$planName}")
            ->line("Monto: \${$this->payment->total_amount} {$this->payment->currency}")
            ->line("Referencia: {$reference}")
            ->lineIf($endsAt !== null, "Tu plan está vigente hasta el {$endsAt}.")
            ->line('Tu suscripción ya se encuentra activa.')
            ->action('Ir a mi panel', url('/dashboard'))
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
