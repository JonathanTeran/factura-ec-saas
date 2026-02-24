<?php

namespace App\Services\Payment;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $gatewayPaymentId = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
        public readonly array $gatewayResponse = [],
    ) {}

    public static function success(
        string $transactionId,
        string $gatewayPaymentId,
        array $gatewayResponse = []
    ): self {
        return new self(
            success: true,
            transactionId: $transactionId,
            gatewayPaymentId: $gatewayPaymentId,
            gatewayResponse: $gatewayResponse,
        );
    }

    public static function failure(
        string $errorMessage,
        ?string $errorCode = null,
        array $gatewayResponse = []
    ): self {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            gatewayResponse: $gatewayResponse,
        );
    }

    public function failed(): bool
    {
        return !$this->success;
    }
}
