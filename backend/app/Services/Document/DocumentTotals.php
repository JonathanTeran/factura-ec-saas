<?php

namespace App\Services\Document;

/**
 * Cálculo de líneas y totales de un comprobante a partir de sus ítems.
 *
 * Es la única implementación del cálculo en el servidor: la usan la
 * conversión de proformas, las facturas recurrentes y cualquier flujo que
 * no reciba los totales ya calculados. Replica la lógica del panel
 * (frontend/src/lib/document-calc.ts): se agrupa por código SRI de IVA, no
 * por tarifa, para que "No objeto" y "Exento" (tarifa 0) no se confundan
 * con el 0%.
 */
class DocumentTotals
{
    /** Código SRI (codigoPorcentaje) → tarifa que grava. */
    public const RATE_BY_CODE = [
        '0' => 0.0,
        '2' => 12.0,
        '3' => 14.0,
        '4' => 15.0,
        '5' => 5.0,
        '6' => 0.0,   // No objeto de impuesto
        '7' => 0.0,   // Exento de IVA
        '8' => 8.0,
        '10' => 13.0,
    ];

    /** Tarifa → código SRI "gravado" natural de esa tarifa. */
    public const CODE_BY_RATE = [
        '0' => '0',
        '5' => '5',
        '8' => '8',
        '12' => '2',
        '13' => '10',
        '14' => '3',
        '15' => '4',
    ];

    /** Tarifa vigente de IVA en Ecuador (desde abril 2024). */
    public const DEFAULT_RATE = 15.0;

    public const DEFAULT_CODE = '4';

    /**
     * Normaliza los ítems (calcula subtotal, base e IVA, deriva el código o
     * la tarifa que falte) y devuelve los totales del comprobante.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public static function fromItems(array $items): array
    {
        $totals = [
            'subtotal_no_tax' => 0.0,
            'subtotal_0' => 0.0,
            'subtotal_5' => 0.0,
            'subtotal_8' => 0.0,
            'subtotal_12' => 0.0,
            'subtotal_13' => 0.0,
            'subtotal_15' => 0.0,
            'total_discount' => 0.0,
            'total_tax' => 0.0,
            'total' => 0.0,
        ];

        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            $line = self::normalizeItem($item, $index);
            $normalized[] = $line;

            $totals['total_discount'] += $line['discount'];
            $totals['total_tax'] += $line['tax_value'];

            match ($line['tax_percentage_code']) {
                '0' => $totals['subtotal_0'] += $line['subtotal'],
                '5' => $totals['subtotal_5'] += $line['subtotal'],
                '8' => $totals['subtotal_8'] += $line['subtotal'],
                '2' => $totals['subtotal_12'] += $line['subtotal'],
                '10' => $totals['subtotal_13'] += $line['subtotal'],
                '4', '3' => $totals['subtotal_15'] += $line['subtotal'],
                default => $totals['subtotal_no_tax'] += $line['subtotal'],
            };
        }

        $subtotals = $totals['subtotal_0'] + $totals['subtotal_5'] + $totals['subtotal_8']
            + $totals['subtotal_12'] + $totals['subtotal_13'] + $totals['subtotal_15']
            + $totals['subtotal_no_tax'];
        $totals['total'] = $subtotals + $totals['total_tax'];

        foreach ($totals as $key => $value) {
            $totals[$key] = self::round($value);
        }

        return ['items' => $normalized, 'totals' => $totals];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function normalizeItem(array $item, int $index = 0): array
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $discount = self::round((float) ($item['discount'] ?? 0));

        [$code, $rate] = self::resolveTax($item);

        $gross = self::round($quantity * $unitPrice);
        $subtotal = isset($item['subtotal']) && $item['subtotal'] !== null && $item['subtotal'] !== ''
            ? self::round((float) $item['subtotal'])
            : max(0.0, self::round($gross - $discount));
        $taxBase = isset($item['tax_base']) && $item['tax_base'] !== null && $item['tax_base'] !== ''
            ? self::round((float) $item['tax_base'])
            : $subtotal;
        $taxValue = isset($item['tax_value']) && $item['tax_value'] !== null && $item['tax_value'] !== ''
            ? self::round((float) $item['tax_value'])
            : self::round($taxBase * ($rate / 100));

        $mainCode = trim((string) ($item['main_code'] ?? ''));

        return [
            'product_id' => $item['product_id'] ?? null,
            // El SRI exige codigoPrincipal: los ítems manuales reciben uno.
            'main_code' => $mainCode !== '' ? $mainCode : 'ITEM-'.($index + 1),
            'aux_code' => $item['aux_code'] ?? null,
            'description' => (string) ($item['description'] ?? ''),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'tax_code' => (string) ($item['tax_code'] ?? '2'),
            'tax_percentage_code' => $code,
            'tax_rate' => $rate,
            'tax_base' => $taxBase,
            'tax_value' => $taxValue,
        ];
    }

    /**
     * Código y tarifa efectivos de un ítem: el código manda; si falta, se
     * deriva de la tarifa; si no hay ninguno, IVA vigente (15%).
     *
     * @return array{0: string, 1: float}
     */
    public static function resolveTax(array $item): array
    {
        $code = isset($item['tax_percentage_code']) ? (string) $item['tax_percentage_code'] : '';
        $hasRate = isset($item['tax_rate']) && $item['tax_rate'] !== '' && $item['tax_rate'] !== null;
        $rate = $hasRate ? (float) $item['tax_rate'] : null;

        if ($code !== '' && array_key_exists($code, self::RATE_BY_CODE)) {
            return [$code, $rate ?? self::RATE_BY_CODE[$code]];
        }

        if ($rate !== null) {
            return [self::codeForRate($rate), $rate];
        }

        return [self::DEFAULT_CODE, self::DEFAULT_RATE];
    }

    public static function codeForRate(float $rate): string
    {
        $key = (string) (int) round($rate);

        return self::CODE_BY_RATE[$key] ?? self::DEFAULT_CODE;
    }

    public static function rateForCode(string $code): float
    {
        return self::RATE_BY_CODE[$code] ?? self::DEFAULT_RATE;
    }

    public static function round(float $value): float
    {
        return round($value, 2);
    }
}
