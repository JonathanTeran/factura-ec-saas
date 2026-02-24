<?php

namespace App\Enums;

enum FiscalPeriodStatus: string
{
    case OPEN = 'open';
    case CLOSING = 'closing';
    case CLOSED = 'closed';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Abierto',
            self::CLOSING => 'En cierre',
            self::CLOSED => 'Cerrado',
            self::LOCKED => 'Bloqueado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'green',
            self::CLOSING => 'yellow',
            self::CLOSED => 'gray',
            self::LOCKED => 'red',
        };
    }

    public function allowsEntries(): bool
    {
        return $this === self::OPEN;
    }
}
