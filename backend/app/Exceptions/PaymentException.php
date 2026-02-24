<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public function __construct(
        string $message = 'Error procesando el pago',
        public readonly ?string $gateway = null,
        public readonly ?string $gatewayCode = null,
        public readonly ?string $transactionId = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return array_filter([
            'gateway' => $this->gateway,
            'gateway_code' => $this->gatewayCode,
            'transaction_id' => $this->transactionId,
        ]);
    }
}
