<?php

namespace App\Exceptions;

use Exception;

class DocumentProcessingException extends Exception
{
    public function __construct(
        string $message = 'Error procesando el documento',
        public readonly ?int $documentId = null,
        public readonly ?string $stage = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return array_filter([
            'document_id' => $this->documentId,
            'stage' => $this->stage,
        ]);
    }
}
