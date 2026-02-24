<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\TaxFormType;
use App\Services\Accounting\ATSService;
use App\Services\Accounting\TaxFormService;
use Livewire\Component;

class ATSGenerate extends Component
{
    public int $year;
    public int $month;
    public string $activeTab = 'ventas';
    public ?array $atsData = null;
    public bool $isGenerated = false;

    protected $queryString = [
        'year' => ['except' => ''],
        'month' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->year = $this->year ?: now()->year;
        $this->month = $this->month ?: now()->month;
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            $years[] = $y;
        }
        return $years;
    }

    public function getMonthsProperty(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function generate(): void
    {
        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        try {
            $service = app(ATSService::class)->forCompany($company);
            $this->atsData = $service->generate($this->year, $this->month);
            $this->isGenerated = true;
            $this->activeTab = 'ventas';

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'ATS generado correctamente.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al generar ATS: ' . $e->getMessage(),
            ]);
        }
    }

    public function getStatsProperty(): array
    {
        if (!$this->atsData) {
            return [
                'ventas' => 0,
                'compras' => 0,
                'retenciones' => 0,
                'anulados' => 0,
                'total_ventas' => 0,
                'total_compras' => 0,
            ];
        }

        return [
            'ventas' => count($this->atsData['ventas'] ?? []),
            'compras' => count($this->atsData['compras'] ?? []),
            'retenciones' => count($this->atsData['retenciones'] ?? []),
            'anulados' => count($this->atsData['anulados'] ?? []),
            'total_ventas' => collect($this->atsData['ventas'] ?? [])->sum('baseImponible')
                + collect($this->atsData['ventas'] ?? [])->sum('baseImpGrav'),
            'total_compras' => collect($this->atsData['compras'] ?? [])->sum('baseImponible')
                + collect($this->atsData['compras'] ?? [])->sum('baseImpGrav'),
        ];
    }

    public function downloadXml(): void
    {
        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            return;
        }

        try {
            $service = app(ATSService::class)->forCompany($company);
            $xml = $service->generateXml($this->year, $this->month);

            // Guardar registro de generacion
            $taxFormService = app(TaxFormService::class)->forCompany($company);
            $taxFormService->saveSubmission([
                'year' => $this->year,
                'month' => $this->month,
                'stats' => $this->stats,
            ], TaxFormType::ATS);

            $filename = "ATS_{$company->ruc}_{$this->year}_{$this->month}.xml";

            $this->dispatch('download-xml', [
                'content' => $xml,
                'filename' => $filename,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'XML del ATS descargado correctamente.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al generar XML: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.panel.accounting.ats-generate', [
            'years' => $this->years,
            'months' => $this->months,
            'stats' => $this->stats,
        ])->layout('layouts.tenant', ['title' => 'Generar ATS']);
    }
}
