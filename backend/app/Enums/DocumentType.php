<?php

namespace App\Enums;

enum DocumentType: string
{
    case FACTURA = '01';
    case LIQUIDACION_COMPRA = '03';
    case NOTA_CREDITO = '04';
    case NOTA_DEBITO = '05';
    case GUIA_REMISION = '06';
    case RETENCION = '07';

    public function label(): string
    {
        return match ($this) {
            self::FACTURA => 'Factura',
            self::LIQUIDACION_COMPRA => 'Liquidación de Compra',
            self::NOTA_CREDITO => 'Nota de Crédito',
            self::NOTA_DEBITO => 'Nota de Débito',
            self::GUIA_REMISION => 'Guía de Remisión',
            self::RETENCION => 'Comprobante de Retención',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::FACTURA => 'FAC',
            self::LIQUIDACION_COMPRA => 'LIQ',
            self::NOTA_CREDITO => 'NC',
            self::NOTA_DEBITO => 'ND',
            self::GUIA_REMISION => 'GR',
            self::RETENCION => 'RET',
        };
    }
}
