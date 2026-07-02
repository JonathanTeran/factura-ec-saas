<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case INVOICED = 'invoiced';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::DRAFT    => 'Borrador',
            self::SENT     => 'Enviada',
            self::ACCEPTED => 'Aceptada',
            self::REJECTED => 'Rechazada',
            self::INVOICED => 'Facturada',
            self::EXPIRED  => 'Vencida',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT    => 'gray',
            self::SENT     => 'blue',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::INVOICED => 'purple',
            self::EXPIRED  => 'orange',
        };
    }
}
