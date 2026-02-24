<?php

namespace App\Exceptions;

use Exception;

class TenantInactiveException extends Exception
{
    public function __construct(
        public readonly ?int $tenantId = null,
        public readonly ?string $status = null,
        ?\Throwable $previous = null,
    ) {
        $message = 'Tu cuenta no esta activa.';
        if ($status === 'suspended') {
            $message = 'Tu cuenta ha sido suspendida. Contacta soporte para mas informacion.';
        } elseif ($status === 'cancelled') {
            $message = 'Tu cuenta ha sido cancelada.';
        }

        parent::__construct($message, 403, $previous);
    }

    public function context(): array
    {
        return array_filter([
            'tenant_id' => $this->tenantId,
            'status' => $this->status,
        ]);
    }
}
