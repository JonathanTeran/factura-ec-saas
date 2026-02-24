<?php

namespace App\Enums;

enum AccountType: string
{
    case ACTIVO = 'activo';
    case PASIVO = 'pasivo';
    case PATRIMONIO = 'patrimonio';
    case INGRESO = 'ingreso';
    case COSTO = 'costo';
    case GASTO = 'gasto';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::PASIVO => 'Pasivo',
            self::PATRIMONIO => 'Patrimonio',
            self::INGRESO => 'Ingreso',
            self::COSTO => 'Costo',
            self::GASTO => 'Gasto',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVO => 'blue',
            self::PASIVO => 'red',
            self::PATRIMONIO => 'purple',
            self::INGRESO => 'green',
            self::COSTO => 'orange',
            self::GASTO => 'amber',
        };
    }

    public function defaultNature(): string
    {
        return match ($this) {
            self::ACTIVO, self::COSTO, self::GASTO => 'debit',
            self::PASIVO, self::PATRIMONIO, self::INGRESO => 'credit',
        };
    }
}
