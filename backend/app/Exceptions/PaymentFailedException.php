<?php

namespace App\Exceptions;

class PaymentFailedException extends PaymentException
{
    public function __construct(
        string $message = 'El pago fue rechazado',
        ?string $gateway = null,
        ?string $gatewayCode = null,
        ?string $transactionId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $gateway, $gatewayCode, $transactionId, 0, $previous);
    }
}
