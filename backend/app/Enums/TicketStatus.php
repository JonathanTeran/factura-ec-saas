<?php

namespace App\Enums;

enum TicketStatus: string
{
    case OPEN             = 'open';
    case IN_PROGRESS      = 'in_progress';
    case WAITING_CUSTOMER = 'waiting_customer';
    case RESOLVED         = 'resolved';
    case CLOSED           = 'closed';

    public function label(): string
    {
        return match($this) {
            self::OPEN             => 'Abierto',
            self::IN_PROGRESS      => 'En proceso',
            self::WAITING_CUSTOMER => 'Esperando respuesta',
            self::RESOLVED         => 'Resuelto',
            self::CLOSED           => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OPEN             => 'blue',
            self::IN_PROGRESS      => 'yellow',
            self::WAITING_CUSTOMER => 'purple',
            self::RESOLVED         => 'green',
            self::CLOSED           => 'gray',
        };
    }
}
