<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\FiscalPeriodStatus;
use App\Models\Accounting\FiscalPeriod;
use App\Services\Accounting\FiscalPeriodService;
use Livewire\Component;

class FiscalPeriodManager extends Component
{
    public int $selectedYear;

    protected $queryString = [
        'selectedYear' => ['except' => '', 'as' => 'year'],
    ];

    public function mount(): void
    {
        $this->selectedYear = $this->selectedYear ?: now()->year;
    }

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getPeriodsProperty()
    {
        $company = $this->company;

        if (!$company) {
            return collect();
        }

        return FiscalPeriod::where('company_id', $company->id)
            ->where('year', $this->selectedYear)
            ->orderByRaw("CASE WHEN period_type = 'annual' THEN 13 ELSE month END ASC")
            ->get();
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--) {
            $years[] = $y;
        }
        return $years;
    }

    public function getMonthLabelsProperty(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    public function createPeriods(): void
    {
        $company = $this->company;

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        $existingCount = FiscalPeriod::where('company_id', $company->id)
            ->where('year', $this->selectedYear)
            ->count();

        if ($existingCount > 0) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Ya existen periodos para el ano ' . $this->selectedYear . '.',
            ]);
            return;
        }

        try {
            $service = app(FiscalPeriodService::class)->forCompany($company);
            $service->createPeriodsForYear($this->selectedYear);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Periodos fiscales creados para ' . $this->selectedYear . '.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function closePeriod(int $periodId): void
    {
        $period = FiscalPeriod::findOrFail($periodId);
        $company = $this->company;

        if (!$company) {
            return;
        }

        try {
            $service = app(FiscalPeriodService::class)->forCompany($company);
            $service->closePeriod($period);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Periodo cerrado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function lockPeriod(int $periodId): void
    {
        $period = FiscalPeriod::findOrFail($periodId);
        $company = $this->company;

        if (!$company) {
            return;
        }

        try {
            $service = app(FiscalPeriodService::class)->forCompany($company);
            $service->lockPeriod($period);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Periodo bloqueado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function reopenPeriod(int $periodId): void
    {
        $period = FiscalPeriod::findOrFail($periodId);
        $company = $this->company;

        if (!$company) {
            return;
        }

        try {
            $service = app(FiscalPeriodService::class)->forCompany($company);
            $service->reopenPeriod($period);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Periodo reabierto correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function generateClosingEntry(): void
    {
        $company = $this->company;

        if (!$company) {
            return;
        }

        try {
            $service = app(FiscalPeriodService::class)->forCompany($company);
            $entry = $service->generateClosingEntry($this->selectedYear);

            if ($entry) {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Asiento de cierre generado: ' . $entry->entry_number,
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => 'No hay saldos en cuentas de resultados para cerrar.',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function generateOpeningEntry(): void
    {
        $company = $this->company;

        if (!$company) {
            return;
        }

        try {
            $service = app(FiscalPeriodService::class)->forCompany($company);
            $entry = $service->generateOpeningEntry($this->selectedYear);

            if ($entry) {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Asiento de apertura generado: ' . $entry->entry_number,
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => 'No hay saldos de balance del ano anterior para abrir.',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.panel.accounting.fiscal-period-manager', [
            'periods' => $this->periods,
            'years' => $this->years,
            'monthLabels' => $this->monthLabels,
        ])->layout('layouts.tenant', ['title' => 'Periodos Fiscales']);
    }
}
