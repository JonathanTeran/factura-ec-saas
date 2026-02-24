<?php

namespace App\Enums;

enum JournalEntrySource: string
{
    case MANUAL = 'manual';
    case AUTO_INVOICE = 'auto_invoice';
    case AUTO_CREDIT_NOTE = 'auto_credit_note';
    case AUTO_DEBIT_NOTE = 'auto_debit_note';
    case AUTO_RETENTION = 'auto_retention';
    case AUTO_PURCHASE = 'auto_purchase';
    case AUTO_LIQUIDATION = 'auto_liquidation';
    case CLOSING = 'closing';
    case OPENING = 'opening';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::AUTO_INVOICE => 'Factura (Auto)',
            self::AUTO_CREDIT_NOTE => 'Nota de Credito (Auto)',
            self::AUTO_DEBIT_NOTE => 'Nota de Debito (Auto)',
            self::AUTO_RETENTION => 'Retencion (Auto)',
            self::AUTO_PURCHASE => 'Compra (Auto)',
            self::AUTO_LIQUIDATION => 'Liquidacion (Auto)',
            self::CLOSING => 'Cierre',
            self::OPENING => 'Apertura',
            self::ADJUSTMENT => 'Ajuste',
        };
    }

    public function isAutomatic(): bool
    {
        return in_array($this, [
            self::AUTO_INVOICE,
            self::AUTO_CREDIT_NOTE,
            self::AUTO_DEBIT_NOTE,
            self::AUTO_RETENTION,
            self::AUTO_PURCHASE,
            self::AUTO_LIQUIDATION,
        ]);
    }
}
