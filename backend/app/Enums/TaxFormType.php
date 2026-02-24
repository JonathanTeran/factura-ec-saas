<?php

namespace App\Enums;

enum TaxFormType: string
{
    case F101 = 'f101';
    case F102 = 'f102';
    case F103 = 'f103';
    case F104 = 'f104';
    case ATS = 'ats';
    case RDEP = 'rdep';
    case GASTOS_PERSONALES = 'gastos_personales';

    public function label(): string
    {
        return match ($this) {
            self::F101 => 'Formulario 101 - IR Sociedades',
            self::F102 => 'Formulario 102 - IR Personas Naturales',
            self::F103 => 'Formulario 103 - Retenciones en la Fuente',
            self::F104 => 'Formulario 104 - IVA',
            self::ATS => 'Anexo Transaccional Simplificado',
            self::RDEP => 'RDEP - Relacion de Dependencia',
            self::GASTOS_PERSONALES => 'Gastos Personales',
        };
    }

    public function frequency(): string
    {
        return match ($this) {
            self::F103, self::F104, self::ATS => 'monthly',
            self::F101, self::F102, self::RDEP, self::GASTOS_PERSONALES => 'annual',
        };
    }
}
