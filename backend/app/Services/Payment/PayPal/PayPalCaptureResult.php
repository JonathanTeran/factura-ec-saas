<?php

namespace App\Services\Payment\PayPal;

use App\Models\Billing\Payment;

/**
 * Resultado de confirmar un pago de PayPal: completado (plan activo), en
 * revisión (PayPal o el admin deben confirmarlo) o fallido (hay que reintentar).
 */
final class PayPalCaptureResult
{
    public const COMPLETED = 'completed';

    public const PROCESSING = 'processing';

    public const FAILED = 'failed';

    private function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly Payment $payment,
    ) {}

    public static function completed(Payment $payment): self
    {
        return new self(self::COMPLETED, '¡Pago recibido! Tu plan ya está activo.', $payment);
    }

    public static function processing(Payment $payment, string $message): self
    {
        return new self(self::PROCESSING, $message, $payment);
    }

    public static function failed(Payment $payment, string $message): self
    {
        return new self(self::FAILED, $message, $payment);
    }
}
