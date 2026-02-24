<?php

namespace App\Exceptions;

class SriRejectionException extends SriException
{
    public function __construct(
        string $message = 'Documento rechazado por el SRI',
        ?int $documentId = null,
        ?string $accessKey = null,
        public readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $documentId, $accessKey, $code, $previous);
    }

    public function context(): array
    {
        return array_merge(parent::context(), [
            'sri_errors' => $this->errors,
        ]);
    }
}
