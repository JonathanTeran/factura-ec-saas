<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case INCOMPLETE = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::TRIALING => 'En prueba',
            self::ACTIVE => 'Activa',
            self::PAST_DUE => 'Pago pendiente',
            self::CANCELLED => 'Cancelada',
            self::EXPIRED => 'Expirada',
            self::INCOMPLETE => 'Incompleta',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TRIALING => 'blue',
            self::ACTIVE => 'green',
            self::PAST_DUE => 'yellow',
            self::CANCELLED => 'red',
            self::EXPIRED => 'gray',
            self::INCOMPLETE => 'orange',
        };
    }
}
