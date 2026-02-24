<?php

namespace App\Imports;

use App\Models\Tenant\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class SupplierImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, SkipsOnFailure
{
    use SkipsFailures;

    public function __construct(private int $tenantId) {}

    public function model(array $row)
    {
        return new Supplier([
            'tenant_id' => $this->tenantId,
            'identification_type' => $row['tipo_identificacion'] ?? '04',
            'identification' => (string) $row['identificacion'],
            'business_name' => $row['razon_social'],
            'commercial_name' => $row['nombre_comercial'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['telefono'] ?? null,
            'address' => $row['direccion'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'identificacion' => ['required', 'string'],
            'razon_social' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }
}
