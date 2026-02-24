<?php

namespace App\Enums;

enum AccountingStandard: string
{
    case NIIF_FULL = 'niif_full';
    case NIIF_PYMES = 'niif_pymes';

    public function label(): string
    {
        return match ($this) {
            self::NIIF_FULL => 'NIIF Completas',
            self::NIIF_PYMES => 'NIIF para PYMES',
        };
    }
}
