<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Detalle de partidos del árbitro (para conciliar con los pagos de la FEF).
 */
class RefereeReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Fecha', 'Partido', 'Campeonato', 'Rol', 'Valor ($)', 'Estado', 'Factura'];
    }

    public function title(): string
    {
        return 'Partidos';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
