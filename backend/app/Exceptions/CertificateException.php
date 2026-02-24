<?php

namespace App\Exceptions;

use Exception;

class CertificateException extends Exception
{
    public function __construct(
        string $message = 'Error con el certificado digital',
        public readonly ?int $companyId = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
        ]);
    }
}
