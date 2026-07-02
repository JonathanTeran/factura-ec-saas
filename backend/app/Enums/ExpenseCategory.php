<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case PURCHASE      = 'purchase';
    case SERVICE       = 'service';
    case RENT          = 'rent';
    case UTILITIES     = 'utilities';
    case TRANSPORT     = 'transport';
    case FOOD          = 'food';
    case HEALTH        = 'health';
    case EDUCATION     = 'education';
    case ENTERTAINMENT = 'entertainment';
    case OTHER         = 'other';

    public function label(): string
    {
        return match($this) {
            self::PURCHASE      => 'Compra de mercadería',
            self::SERVICE       => 'Servicios',
            self::RENT          => 'Arriendo',
            self::UTILITIES     => 'Servicios básicos',
            self::TRANSPORT     => 'Transporte',
            self::FOOD          => 'Alimentación',
            self::HEALTH        => 'Salud',
            self::EDUCATION     => 'Educación',
            self::ENTERTAINMENT => 'Entretenimiento',
            self::OTHER         => 'Otros',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PURCHASE      => 'blue',
            self::SERVICE       => 'purple',
            self::RENT          => 'orange',
            self::UTILITIES     => 'yellow',
            self::TRANSPORT     => 'cyan',
            self::FOOD          => 'green',
            self::HEALTH        => 'red',
            self::EDUCATION     => 'indigo',
            self::ENTERTAINMENT => 'pink',
            self::OTHER         => 'gray',
        };
    }
}
