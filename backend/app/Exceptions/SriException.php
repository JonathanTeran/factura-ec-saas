<?php

namespace App\Exceptions;

use Exception;

class SriException extends Exception
{
    public function __construct(
        string $message = 'Error en el servicio del SRI',
        public readonly ?int $documentId = null,
        public readonly ?string $accessKey = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return array_filter([
            'document_id' => $this->documentId,
            'access_key' => $this->accessKey,
        ]);
    }
}
