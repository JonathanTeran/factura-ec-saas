<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio al crear un documento electrónico (suscripción, límite
 * del plan, empresa incompleta, punto de emisión inactivo...). Lleva el
 * código HTTP con el que el panel/API lo respondían para no cambiar el
 * contrato de las respuestas.
 */
class DocumentCreationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 422,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
