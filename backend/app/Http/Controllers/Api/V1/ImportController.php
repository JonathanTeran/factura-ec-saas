<?php

namespace App\Http\Controllers\Api\V1;

use App\Imports\CustomerImport;
use App\Imports\ProductImport;
use App\Imports\SupplierImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends ApiController
{
    /**
     * Import customers from CSV/Excel file.
     *
     * @operationId importCustomers
     */
    public function customers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $import = new CustomerImport($tenantId);

        Excel::import($import, $request->file('file'));

        return $this->success(
            $this->buildImportResponse($import),
            'Importación de clientes completada'
        );
    }

    /**
     * Import products from CSV/Excel file.
     *
     * @operationId importProducts
     */
    public function products(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $import = new ProductImport($tenantId);

        Excel::import($import, $request->file('file'));

        return $this->success(
            $this->buildImportResponse($import),
            'Importación de productos completada'
        );
    }

    /**
     * Import suppliers from CSV/Excel file.
     *
     * @operationId importSuppliers
     */
    public function suppliers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $import = new SupplierImport($tenantId);

        Excel::import($import, $request->file('file'));

        return $this->success(
            $this->buildImportResponse($import),
            'Importación de proveedores completada'
        );
    }

    /**
     * Download a template Excel file with the correct headers.
     *
     * @operationId downloadImportTemplate
     */
    public function downloadTemplate(string $type): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $headers = match ($type) {
            'customers' => [
                'tipo_identificacion', 'identificacion', 'nombre', 'razon_social',
                'email', 'telefono', 'direccion',
            ],
            'products' => [
                'codigo', 'nombre', 'descripcion', 'tipo', 'precio_unitario',
                'codigo_impuesto', 'codigo_porcentaje', 'tarifa_impuesto',
                'controla_stock', 'stock_actual', 'stock_minimo', 'unidad_medida',
            ],
            'suppliers' => [
                'tipo_identificacion', 'identificacion', 'razon_social',
                'nombre_comercial', 'email', 'telefono', 'direccion',
            ],
            default => null,
        };

        if ($headers === null) {
            return $this->error('Tipo de plantilla no válido. Use: customers, products, suppliers', 400);
        }

        return Excel::download(
            new \App\Exports\TemplateExport($headers),
            "plantilla_{$type}.xlsx"
        );
    }

    private function buildImportResponse($import): array
    {
        $failures = $import->failures();

        $errors = [];
        foreach ($failures as $failure) {
            $errors[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }

        return [
            'failed' => count($failures),
            'errors' => $errors,
        ];
    }
}
