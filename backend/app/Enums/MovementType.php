<?php

namespace App\Enums;

enum MovementType: string
{
    case INITIAL = 'initial';
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case RETURN = 'return';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case DAMAGE = 'damage';
    case EXPIRED = 'expired';
    case PRODUCTION_IN = 'production_in';
    case PRODUCTION_OUT = 'production_out';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL => 'Inventario inicial',
            self::PURCHASE => 'Compra',
            self::SALE => 'Venta',
            self::RETURN => 'Devolución',
            self::ADJUSTMENT_IN => 'Ajuste entrada',
            self::ADJUSTMENT_OUT => 'Ajuste salida',
            self::TRANSFER_IN => 'Transferencia entrada',
            self::TRANSFER_OUT => 'Transferencia salida',
            self::DAMAGE => 'Daño/Pérdida',
            self::EXPIRED => 'Vencido',
            self::PRODUCTION_IN => 'Producción entrada',
            self::PRODUCTION_OUT => 'Producción salida',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::INITIAL => 'heroicon-o-clipboard-document-list',
            self::PURCHASE => 'heroicon-o-shopping-cart',
            self::SALE => 'heroicon-o-banknotes',
            self::RETURN => 'heroicon-o-arrow-uturn-left',
            self::ADJUSTMENT_IN => 'heroicon-o-plus-circle',
            self::ADJUSTMENT_OUT => 'heroicon-o-minus-circle',
            self::TRANSFER_IN => 'heroicon-o-arrow-down-tray',
            self::TRANSFER_OUT => 'heroicon-o-arrow-up-tray',
            self::DAMAGE => 'heroicon-o-exclamation-triangle',
            self::EXPIRED => 'heroicon-o-clock',
            self::PRODUCTION_IN => 'heroicon-o-cog-6-tooth',
            self::PRODUCTION_OUT => 'heroicon-o-cog',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INITIAL => 'blue',
            self::PURCHASE => 'green',
            self::SALE => 'green',
            self::RETURN => 'yellow',
            self::ADJUSTMENT_IN => 'indigo',
            self::ADJUSTMENT_OUT => 'orange',
            self::TRANSFER_IN => 'cyan',
            self::TRANSFER_OUT => 'purple',
            self::DAMAGE => 'red',
            self::EXPIRED => 'red',
            self::PRODUCTION_IN => 'teal',
            self::PRODUCTION_OUT => 'teal',
        };
    }

    public function isIncoming(): bool
    {
        return in_array($this, [
            self::INITIAL,
            self::PURCHASE,
            self::RETURN,
            self::ADJUSTMENT_IN,
            self::TRANSFER_IN,
            self::PRODUCTION_IN,
        ]);
    }

    public function isOutgoing(): bool
    {
        return !$this->isIncoming();
    }

    public function affectsStock(): bool
    {
        return true;
    }
}
