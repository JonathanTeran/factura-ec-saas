<?php

namespace App\Enums;

enum SRICatalogType: string
{
    case IDENTIFICATION_TYPE = 'identification_type';
    case DOCUMENT_TYPE = 'document_type';
    case TAX_CODE = 'tax_code';
    case TAX_RATE = 'tax_rate';
    case PAYMENT_METHOD = 'payment_method';
    case WITHHOLDING_CODE_RENTA = 'retention_ir';
    case WITHHOLDING_CODE_IVA = 'retention_iva';
    case SUPPORTING_DOC_TYPE = 'sustento_code';
    case VOID_REASON = 'void_reason';
    case ICE_CODE = 'ice_code';
    case UNIT_TIME = 'unit_time';
    case UNIT_MEASURE = 'unit_measure';
    case ENVIRONMENT = 'environment';
    case EMISSION_TYPE = 'emission_type';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIFICATION_TYPE => 'Tipos de Identificación',
            self::DOCUMENT_TYPE => 'Tipos de Comprobante',
            self::TAX_CODE => 'Códigos de Impuesto',
            self::TAX_RATE => 'Tarifas de IVA',
            self::PAYMENT_METHOD => 'Formas de Pago',
            self::WITHHOLDING_CODE_RENTA => 'Códigos Retención Renta',
            self::WITHHOLDING_CODE_IVA => 'Códigos Retención IVA',
            self::SUPPORTING_DOC_TYPE => 'Tipos Documento Sustento',
            self::VOID_REASON => 'Motivos de Anulación',
            self::ICE_CODE => 'Códigos ICE',
            self::UNIT_TIME => 'Unidades de Tiempo',
            self::UNIT_MEASURE => 'Unidades de Medida',
            self::ENVIRONMENT => 'Ambientes',
            self::EMISSION_TYPE => 'Tipos de Emisión',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::IDENTIFICATION_TYPE => 'Cédula, RUC, Pasaporte, etc.',
            self::DOCUMENT_TYPE => 'Factura, nota de crédito, guía de remisión, etc.',
            self::TAX_CODE => 'IVA, ICE, IRBPNR',
            self::TAX_RATE => '0%, 5%, 12%, 15%, No objeto, Exento',
            self::PAYMENT_METHOD => 'Efectivo, Tarjeta, Transferencia, etc.',
            self::WITHHOLDING_CODE_RENTA => 'Códigos de retención en la fuente del IR',
            self::WITHHOLDING_CODE_IVA => 'Porcentajes de retención de IVA',
            self::SUPPORTING_DOC_TYPE => 'Códigos de sustento tributario',
            self::VOID_REASON => 'Motivos válidos para anular comprobantes',
            self::ICE_CODE => 'Códigos del Impuesto a Consumos Especiales',
            self::UNIT_TIME => 'Días, meses, años para plazos',
            self::UNIT_MEASURE => 'Unidades, kilogramos, metros, etc.',
            self::ENVIRONMENT => 'Pruebas o Producción',
            self::EMISSION_TYPE => 'Normal o Contingencia',
        };
    }

    /**
     * Obtiene todos los tipos de catálogo disponibles.
     */
    public static function all(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}
