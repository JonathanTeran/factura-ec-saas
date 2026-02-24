<?php

namespace App\Enums;

enum TenantStatus: string
{
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::TRIAL => 'Prueba',
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::CANCELLED => 'Cancelado',
            self::EXPIRED => 'Expirado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TRIAL => 'blue',
            self::ACTIVE => 'green',
            self::SUSPENDED => 'yellow',
            self::CANCELLED => 'red',
            self::EXPIRED => 'gray',
        };
    }

    public function canEmitDocuments(): bool
    {
        return in_array($this, [self::TRIAL, self::ACTIVE]);
    }
}
