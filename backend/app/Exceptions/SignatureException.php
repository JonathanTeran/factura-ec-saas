<?php

namespace App\Exceptions;

class SignatureException extends CertificateException
{
    public function __construct(
        string $message = 'Error al firmar el documento',
        ?int $companyId = null,
        public readonly ?int $documentId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $companyId, 0, $previous);
    }

    public function context(): array
    {
        return array_merge(parent::context(), array_filter([
            'document_id' => $this->documentId,
        ]));
    }
}
