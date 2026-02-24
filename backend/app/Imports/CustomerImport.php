<?php

namespace App\Imports;

use App\Models\Tenant\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class CustomerImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, SkipsOnFailure
{
    use SkipsFailures;

    public function __construct(private int $tenantId) {}

    public function model(array $row)
    {
        return new Customer([
            'tenant_id' => $this->tenantId,
            'identification_type' => $row['tipo_identificacion'] ?? '05',
            'identification' => (string) $row['identificacion'],
            'name' => $row['nombre'] ?? $row['razon_social'],
            'email' => $row['email'] ?? null,
            'phone' => $row['telefono'] ?? null,
            'address' => $row['direccion'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'identificacion' => ['required', 'string'],
            'nombre' => ['required_without:razon_social', 'nullable', 'string'],
            'razon_social' => ['required_without:nombre', 'nullable', 'string'],
            'email' => ['nullable', 'email'],
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }
}
