<?php

namespace App\Enums;

enum TicketCategory: string
{
    case TECHNICAL       = 'technical';
    case BILLING         = 'billing';
    case SRI             = 'sri';
    case GENERAL         = 'general';
    case FEATURE_REQUEST = 'feature_request';

    public function label(): string
    {
        return match($this) {
            self::TECHNICAL       => 'Técnico',
            self::BILLING         => 'Facturación/Pagos',
            self::SRI             => 'SRI/Tributario',
            self::GENERAL         => 'General',
            self::FEATURE_REQUEST => 'Solicitud de función',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::TECHNICAL       => 'blue',
            self::BILLING         => 'purple',
            self::SRI             => 'orange',
            self::GENERAL         => 'gray',
            self::FEATURE_REQUEST => 'green',
        };
    }
}
