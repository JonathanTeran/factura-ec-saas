<?php

namespace App\Exceptions;

class PaymentGatewayException extends PaymentException
{
    public function __construct(
        string $message = 'Error en el gateway de pagos',
        ?string $gateway = null,
        ?string $gatewayCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $gateway, $gatewayCode, null, 0, $previous);
    }
}
