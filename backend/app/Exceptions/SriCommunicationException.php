<?php

namespace App\Exceptions;

class SriCommunicationException extends SriException
{
    public function __construct(
        string $message = 'No se pudo comunicar con el SRI',
        ?int $documentId = null,
        ?string $accessKey = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $documentId, $accessKey, $code, $previous);
    }
}
