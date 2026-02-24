<?php

namespace App\Exceptions;

class InvalidCertificateException extends CertificateException
{
    public function __construct(
        string $reason = 'Certificado digital invalido',
        ?int $companyId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($reason, $companyId, 0, $previous);
    }
}
