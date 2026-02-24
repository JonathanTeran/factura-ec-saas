<?php

namespace App\Notifications;

use App\Models\Billing\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Pago rechazado - Accion requerida')
            ->error()
            ->greeting("Hola {$notifiable->name},")
            ->line("Lamentablemente, tu pago por transferencia bancaria no ha podido ser aprobado.")
            ->line("Referencia: {$this->payment->transaction_id}")
            ->line("Monto: \${$this->payment->total_amount} {$this->payment->currency}");

        if ($this->reason) {
            $mail->line("Motivo: {$this->reason}");
        }

        return $mail
            ->line('Por favor verifica los datos de tu transferencia e intenta nuevamente.')
            ->action('Reintentar Pago', url('/panel/settings/billing'))
            ->line('Si crees que esto es un error, contacta a nuestro equipo de soporte.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_rejected',
            'payment_id' => $this->payment->id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => $this->payment->total_amount,
            'reason' => $this->reason,
            'message' => 'Tu pago ha sido rechazado',
        ];
    }
}
