<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio al enviar un documento al SRI (estado no enviable,
 * firma faltante, reglas locales del SRI...). `errors` trae el detalle de
 * la pre-validación cuando aplica.
 */
class DocumentSendException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 400,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
