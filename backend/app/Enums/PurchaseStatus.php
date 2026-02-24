<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case REGISTERED = 'registered';
    case WITHHOLDING_ISSUED = 'withholding_issued';
    case PAID = 'paid';
    case VOIDED = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registrada',
            self::WITHHOLDING_ISSUED => 'Retencion Emitida',
            self::PAID => 'Pagada',
            self::VOIDED => 'Anulada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGISTERED => 'blue',
            self::WITHHOLDING_ISSUED => 'yellow',
            self::PAID => 'green',
            self::VOIDED => 'gray',
        };
    }
}
