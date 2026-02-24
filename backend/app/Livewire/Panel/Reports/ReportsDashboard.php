<?php

namespace App\Livewire\Panel\Reports;

use App\Exports\ReportExport;
use App\Services\Report\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportsDashboard extends Component
{
    public string $dateRange = 'this_month';
    public string $startDate = '';
    public string $endDate = '';
    public string $reportType = 'sales';
    public string $groupBy = 'day';

    // ATS specific
    public int $atsYear;
    public int $atsMonth;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->atsYear = (int) now()->year;
        $this->atsMonth = (int) now()->month;
    }

    public function updatedDateRange(): void
    {
        match ($this->dateRange) {
            'today' => $this->setDates(now(), now()),
            'yesterday' => $this->setDates(now()->subDay(), now()->subDay()),
            'this_week' => $this->setDates(now()->startOfWeek(), now()),
            'last_week' => $this->setDates(now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()),
            'this_month' => $this->setDates(now()->startOfMonth(), now()),
            'last_month' => $this->setDates(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()),
            'this_quarter' => $this->setDates(now()->firstOfQuarter(), now()),
            'this_year' => $this->setDates(now()->startOfYear(), now()),
            'custom' => null,
            default => null,
        };
    }

    private function setDates(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }

    public function getReportDataProperty(): array
    {
        $tenant = auth()->user()->tenant;
        $reportService = app(ReportService::class)->forTenant($tenant);

        $from = Carbon::parse($this->startDate);
        $to = Carbon::parse($this->endDate)->endOfDay();

        return match ($this->reportType) {
            'sales' => $reportService->getSalesReport($from, $to, $this->groupBy),
            'tax' => $reportService->getTaxReport($from, $to),
            'customers' => ['data' => $reportService->getTopCustomers($from, $to, 20)],
            'products' => ['data' => $reportService->getTopProducts($from, $to, 20)],
            'status' => ['data' => $reportService->getDocumentsByStatus($from, $to)],
            'comparison' => $reportService->getPeriodComparison($from, $to),
            'ats' => $reportService->getATSData($this->atsYear, $this->atsMonth),
            'withholdings' => ['data' => $reportService->getWithholdingsReport($from, $to)],
            default => [],
        };
    }

    public function getDashboardStatsProperty(): array
    {
        $tenant = auth()->user()->tenant;
        return app(ReportService::class)->forTenant($tenant)->getDashboardStats();
    }

    public function exportReport(string $format)
    {
        $reportData = $this->reportData;
        $reportTitles = [
            'sales' => 'Reporte de Ventas',
            'tax' => 'Reporte de Impuestos',
            'customers' => 'Top Clientes',
            'products' => 'Top Productos',
            'status' => 'Documentos por Estado',
            'comparison' => 'Comparativa de Periodos',
            'ats' => 'Anexo Transaccional Simplificado',
            'withholdings' => 'Reporte de Retenciones',
        ];
        $title = $reportTitles[$this->reportType] ?? 'Reporte';
        $filename = str_replace(' ', '_', strtolower($title)) . "_{$this->startDate}_{$this->endDate}";

        if ($format === 'excel') {
            return Excel::download(
                new ReportExport($this->reportType, $reportData, $this->startDate, $this->endDate),
                "{$filename}.xlsx"
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.report', [
                'reportType' => $this->reportType,
                'reportData' => $reportData,
                'title' => $title,
                'tenantName' => auth()->user()->tenant->name ?? '',
                'from' => Carbon::parse($this->startDate)->format('d/m/Y'),
                'to' => Carbon::parse($this->endDate)->format('d/m/Y'),
            ])->setPaper('a4', 'landscape');

            return response()->streamDownload(
                fn () => print($pdf->output()),
                "{$filename}.pdf"
            );
        }

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Formato no soportado.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.reports.reports-dashboard', [
            'reportData' => $this->reportData,
            'dashboardStats' => $this->dashboardStats,
        ])->layout('layouts.tenant', ['title' => 'Reportes']);
    }
}
