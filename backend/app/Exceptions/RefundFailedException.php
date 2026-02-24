<?php

namespace App\Exceptions;

class RefundFailedException extends PaymentException
{
    public function __construct(
        string $message = 'Error al procesar el reembolso',
        ?string $gateway = null,
        ?string $transactionId = null,
        public readonly ?float $amount = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $gateway, null, $transactionId, 0, $previous);
    }

    public function context(): array
    {
        return array_merge(parent::context(), array_filter([
            'refund_amount' => $this->amount,
        ]));
    }
}
