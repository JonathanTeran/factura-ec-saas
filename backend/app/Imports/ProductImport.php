<?php

namespace App\Imports;

use App\Models\Tenant\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, SkipsOnFailure
{
    use SkipsFailures;

    public function __construct(private int $tenantId) {}

    public function model(array $row)
    {
        return new Product([
            'tenant_id' => $this->tenantId,
            'main_code' => (string) $row['codigo'],
            'name' => $row['nombre'],
            'description' => $row['descripcion'] ?? null,
            'type' => $row['tipo'] ?? 'product',
            'unit_price' => (float) $row['precio_unitario'],
            'tax_code' => $row['codigo_impuesto'] ?? '2',
            'tax_percentage_code' => $row['codigo_porcentaje'] ?? '2',
            'tax_rate' => $row['tarifa_impuesto'] ?? 12,
            'track_inventory' => filter_var($row['controla_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'current_stock' => $row['stock_actual'] ?? 0,
            'min_stock' => $row['stock_minimo'] ?? 0,
            'unit_of_measure' => $row['unidad_medida'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string'],
            'nombre' => ['required', 'string'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'tipo' => ['nullable', 'string', 'in:product,service'],
            'codigo_impuesto' => ['nullable', 'string'],
            'codigo_porcentaje' => ['nullable', 'string'],
            'tarifa_impuesto' => ['nullable', 'numeric', 'min:0'],
            'stock_actual' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }
}
