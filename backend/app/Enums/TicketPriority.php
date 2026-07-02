<?php

namespace App\Enums;

enum TicketPriority: string
{
    case LOW      = 'low';
    case MEDIUM   = 'medium';
    case HIGH     = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::LOW      => 'Baja',
            self::MEDIUM   => 'Media',
            self::HIGH     => 'Alta',
            self::CRITICAL => 'Crítica',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW      => 'gray',
            self::MEDIUM   => 'blue',
            self::HIGH     => 'orange',
            self::CRITICAL => 'red',
        };
    }
}
