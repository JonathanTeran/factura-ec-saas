<?php

namespace App\Enums;

enum IdentificationType: string
{
    case RUC = '04';
    case CEDULA = '05';
    case PASAPORTE = '06';
    case CONSUMIDOR_FINAL = '07';
    case EXTERIOR = '08';

    public function label(): string
    {
        return match ($this) {
            self::RUC => 'RUC',
            self::CEDULA => 'Cédula',
            self::PASAPORTE => 'Pasaporte',
            self::CONSUMIDOR_FINAL => 'Consumidor Final',
            self::EXTERIOR => 'Identificación del Exterior',
        };
    }

    public function maxLength(): int
    {
        return match ($this) {
            self::RUC => 13,
            self::CEDULA => 10,
            self::PASAPORTE => 20,
            self::CONSUMIDOR_FINAL => 13,
            self::EXTERIOR => 20,
        };
    }

    public function validationPattern(): string
    {
        return match ($this) {
            self::RUC => '/^[0-9]{13}$/',
            self::CEDULA => '/^[0-9]{10}$/',
            self::PASAPORTE => '/^[A-Za-z0-9]{6,20}$/',
            self::CONSUMIDOR_FINAL => '/^9999999999999$/',
            self::EXTERIOR => '/^[A-Za-z0-9]{6,20}$/',
        };
    }
}
