<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\TaxFormType;
use App\Services\Accounting\TaxFormService;
use Livewire\Component;

class TaxFormGenerate extends Component
{
    public string $formType;
    public int $year;
    public int $month;
    public ?array $previewData = null;
    public bool $showPreview = false;

    protected $queryString = [
        'year' => ['except' => ''],
        'month' => ['except' => ''],
    ];

    public function mount(string $formType): void
    {
        $this->formType = $formType;
        $this->year = $this->year ?: now()->year;
        $this->month = $this->month ?: now()->month;
    }

    public function getFormTypeEnumProperty(): TaxFormType
    {
        return TaxFormType::from($this->formType);
    }

    public function getIsAnnualProperty(): bool
    {
        return $this->formTypeEnum->frequency() === 'annual';
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

    public function generatePreview(): void
    {
        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        $service = app(TaxFormService::class)->forCompany($company);
        $type = $this->formTypeEnum;

        try {
            $this->previewData = match ($type) {
                TaxFormType::F101 => $service->generateF101($this->year),
                TaxFormType::F102 => $service->generateF102($this->year),
                TaxFormType::F103 => $service->generateF103($this->year, $this->month),
                TaxFormType::F104 => $service->generateF104($this->year, $this->month),
                default => null,
            };

            $this->showPreview = true;
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al generar vista previa: ' . $e->getMessage(),
            ]);
        }
    }

    public function save(): void
    {
        if (!$this->previewData) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero genera la vista previa.',
            ]);
            return;
        }

        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            return;
        }

        $service = app(TaxFormService::class)->forCompany($company);

        try {
            $submission = $service->saveSubmission($this->previewData, $this->formTypeEnum);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Formulario guardado correctamente. ID: ' . $submission->id,
            ]);

            $this->redirect(route('panel.accounting.tax-forms'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.panel.accounting.tax-form-generate', [
            'years' => $this->years,
            'months' => $this->months,
            'isAnnual' => $this->isAnnual,
            'formLabel' => $this->formTypeEnum->label(),
        ])->layout('layouts.tenant', ['title' => $this->formTypeEnum->label()]);
    }
}
