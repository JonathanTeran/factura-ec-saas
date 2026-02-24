<?php

namespace App\Exceptions;

use Carbon\Carbon;

class CertificateExpiredException extends CertificateException
{
    public function __construct(
        ?int $companyId = null,
        public readonly ?Carbon $expiredAt = null,
        ?\Throwable $previous = null,
    ) {
        $message = 'El certificado digital ha expirado';
        if ($expiredAt) {
            $message .= ' el ' . $expiredAt->format('d/m/Y');
        }

        parent::__construct($message, $companyId, 0, $previous);
    }

    public function context(): array
    {
        return array_merge(parent::context(), array_filter([
            'expired_at' => $this->expiredAt?->toIso8601String(),
        ]));
    }
}
