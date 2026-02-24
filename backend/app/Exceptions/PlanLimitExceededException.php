<?php

namespace App\Exceptions;

use Exception;

class PlanLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $resource,
        public readonly int $limit,
        public readonly int $used,
        ?\Throwable $previous = null,
    ) {
        $message = match ($resource) {
            'documents' => "Has alcanzado el limite de {$limit} documentos mensuales de tu plan.",
            'users' => "Has alcanzado el limite de {$limit} usuarios de tu plan.",
            'companies' => "Has alcanzado el limite de {$limit} empresas de tu plan.",
            'emission_points' => "Has alcanzado el limite de {$limit} puntos de emision de tu plan.",
            default => "Has alcanzado el limite de {$resource} de tu plan.",
        };

        parent::__construct($message, 403, $previous);
    }

    public function context(): array
    {
        return [
            'resource' => $this->resource,
            'limit' => $this->limit,
            'used' => $this->used,
        ];
    }
}
