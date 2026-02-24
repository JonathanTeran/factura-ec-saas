<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private string $reportType,
        private array $reportData,
        private string $from,
        private string $to,
    ) {}

    public function array(): array
    {
        return match ($this->reportType) {
            'sales' => $this->formatSalesData(),
            'tax' => $this->formatTaxData(),
            'customers' => $this->formatCustomersData(),
            'products' => $this->formatProductsData(),
            'status' => $this->formatStatusData(),
            default => [],
        };
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'sales' => ['Periodo', 'Cantidad', 'Total ($)', 'Impuesto ($)', 'Promedio ($)'],
            'tax' => ['Concepto', 'Monto ($)'],
            'customers' => ['Cliente', 'Identificacion', 'Documentos', 'Total ($)'],
            'products' => ['Producto', 'Codigo', 'Cantidad Vendida', 'Total ($)'],
            'status' => ['Estado', 'Cantidad'],
            default => [],
        };
    }

    public function title(): string
    {
        $titles = [
            'sales' => 'Ventas',
            'tax' => 'Impuestos',
            'customers' => 'Top Clientes',
            'products' => 'Top Productos',
            'status' => 'Por Estado',
        ];

        return $titles[$this->reportType] ?? 'Reporte';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    private function formatSalesData(): array
    {
        $data = $this->reportData['data'] ?? [];
        $rows = [];

        foreach ($data as $item) {
            $rows[] = [
                $item['period'] ?? $item->period ?? '',
                $item['count'] ?? $item->count ?? 0,
                number_format((float) ($item['total'] ?? $item->total ?? 0), 2),
                number_format((float) ($item['tax'] ?? $item->tax ?? 0), 2),
                number_format((float) ($item['average'] ?? $item->average ?? 0), 2),
            ];
        }

        if (isset($this->reportData['totals'])) {
            $totals = $this->reportData['totals'];
            $rows[] = [];
            $rows[] = [
                'TOTAL',
                $totals['count'] ?? 0,
                number_format((float) ($totals['total'] ?? 0), 2),
                number_format((float) ($totals['tax'] ?? 0), 2),
                number_format((float) ($totals['average'] ?? 0), 2),
            ];
        }

        return $rows;
    }

    private function formatTaxData(): array
    {
        $rows = [];

        if (isset($this->reportData['subtotals'])) {
            foreach ($this->reportData['subtotals'] as $rate => $amount) {
                $rows[] = ["Subtotal IVA {$rate}", number_format($amount, 2)];
            }
        }

        if (isset($this->reportData['total_tax'])) {
            $rows[] = [];
            $rows[] = ['Total IVA', number_format($this->reportData['total_tax'], 2)];
        }

        if (isset($this->reportData['total'])) {
            $rows[] = ['Total General', number_format($this->reportData['total'], 2)];
        }

        return $rows;
    }

    private function formatCustomersData(): array
    {
        $data = $this->reportData['data'] ?? [];
        $rows = [];

        foreach ($data as $item) {
            $rows[] = [
                $item['business_name'] ?? '',
                $item['identification'] ?? '',
                $item['document_count'] ?? 0,
                number_format((float) ($item['total_amount'] ?? 0), 2),
            ];
        }

        return $rows;
    }

    private function formatProductsData(): array
    {
        $data = $this->reportData['data'] ?? [];
        $rows = [];

        foreach ($data as $item) {
            $rows[] = [
                $item['name'] ?? '',
                $item['main_code'] ?? '',
                $item['quantity_sold'] ?? 0,
                number_format((float) ($item['total_amount'] ?? 0), 2),
            ];
        }

        return $rows;
    }

    private function formatStatusData(): array
    {
        $data = $this->reportData['data'] ?? [];
        $rows = [];

        $statusLabels = [
            'draft' => 'Borrador',
            'processing' => 'Procesando',
            'signed' => 'Firmado',
            'sent' => 'Enviado',
            'authorized' => 'Autorizado',
            'rejected' => 'Rechazado',
            'failed' => 'Fallido',
            'voided' => 'Anulado',
        ];

        foreach ($data as $status => $count) {
            $rows[] = [
                $statusLabels[$status] ?? $status,
                $count,
            ];
        }

        return $rows;
    }
}
