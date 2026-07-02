<?php

namespace App\Enums;

enum PersonalExpenseCategory: string
{
    case HOUSING    = 'housing';
    case EDUCATION  = 'education';
    case HEALTH     = 'health';
    case FOOD       = 'food';
    case CLOTHING   = 'clothing';
    case TOURISM    = 'tourism';
    case ART        = 'art';
    case OTHER      = 'other';

    public function label(): string
    {
        return match($this) {
            self::HOUSING   => 'Vivienda',
            self::EDUCATION => 'Educación',
            self::HEALTH    => 'Salud',
            self::FOOD      => 'Alimentación',
            self::CLOTHING  => 'Vestimenta',
            self::TOURISM   => 'Turismo',
            self::ART       => 'Arte y Cultura',
            self::OTHER     => 'Otros',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::HOUSING   => 'blue',
            self::EDUCATION => 'purple',
            self::HEALTH    => 'red',
            self::FOOD      => 'green',
            self::CLOTHING  => 'pink',
            self::TOURISM   => 'cyan',
            self::ART       => 'orange',
            self::OTHER     => 'gray',
        };
    }
}
