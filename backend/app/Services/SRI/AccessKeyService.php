<?php

namespace App\Services\SRI;

use App\Models\SRI\ElectronicDocument;
use Teran\Sri\Utils\ClaveAcceso;

/**
 * La clave de acceso SRI (49 dígitos, Módulo 11) es determinística y no
 * requiere conexión con el SRI, así que se genera desde que el documento
 * es un borrador. El código numérico (8 dígitos aleatorios) queda embebido
 * en la clave; DocumentBuilder lo reutiliza al emitir para que la clave
 * enviada al SRI sea exactamente la misma que se mostró en la vista previa.
 */
class AccessKeyService
{
    /** Posición del código numérico dentro de la clave: fecha(8)+tipo(2)+ruc(13)+ambiente(1)+serie(6)+secuencial(9). */
    public const NUMERIC_CODE_OFFSET = 39;
    public const NUMERIC_CODE_LENGTH = 8;

    public function generate(ElectronicDocument $doc): string
    {
        $doc->loadMissing(['company', 'branch', 'emissionPoint']);

        $serie = str_pad($doc->branch->code, 3, '0', STR_PAD_LEFT)
            . str_pad($doc->emissionPoint->code, 3, '0', STR_PAD_LEFT);

        return ClaveAcceso::generar(
            $doc->issue_date->format('dmY'),
            $doc->document_type->value,
            $doc->company->ruc,
            (string) ($doc->environment ?: $doc->company->sri_environment),
            $serie,
            str_pad((string) $doc->sequential, 9, '0', STR_PAD_LEFT),
            str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT),
        );
    }

    /** Código numérico embebido en una clave de acceso existente. */
    public static function numericCodeFrom(?string $accessKey): ?string
    {
        if (! $accessKey || strlen($accessKey) !== 49) {
            return null;
        }

        return substr($accessKey, self::NUMERIC_CODE_OFFSET, self::NUMERIC_CODE_LENGTH);
    }
}
