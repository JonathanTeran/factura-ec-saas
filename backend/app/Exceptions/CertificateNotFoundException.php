<?php

namespace App\Exceptions;

class CertificateNotFoundException extends CertificateException
{
    public function __construct(
        ?int $companyId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            'La empresa no tiene certificado digital configurado',
            $companyId,
            0,
            $previous,
        );
    }
}
