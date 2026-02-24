<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\TaxFormType;
use App\Models\Accounting\TaxFormSubmission;
use Livewire\Component;

class TaxForms extends Component
{
    public int $selectedYear;
    public int $selectedMonth;

    protected $queryString = [
        'selectedYear' => ['except' => '', 'as' => 'year'],
        'selectedMonth' => ['except' => '', 'as' => 'month'],
    ];

    public function mount(): void
    {
        $this->selectedYear = $this->selectedYear ?: now()->year;
        $this->selectedMonth = $this->selectedMonth ?: now()->month;
    }

    public function getFormCardsProperty(): array
    {
        $company = auth()->user()->tenant->companies()->first();

        if (!$company) {
            return [];
        }

        $cards = [];

        foreach (TaxFormType::cases() as $type) {
            $submission = TaxFormSubmission::where('company_id', $company->id)
                ->where('form_type', $type)
                ->where('fiscal_year', $this->selectedYear)
                ->when(
                    $type->frequency() === 'monthly',
                    fn ($q) => $q->where('fiscal_month', $this->selectedMonth)
                )
                ->latest()
                ->first();

            $cards[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'frequency' => $type->frequency(),
                'status' => $submission?->status ?? 'pending',
                'generated_at' => $submission?->generated_at,
                'submitted_at' => $submission?->submitted_at,
            ];
        }

        return $cards;
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

    public function render()
    {
        return view('livewire.panel.accounting.tax-forms', [
            'formCards' => $this->formCards,
            'years' => $this->years,
            'months' => $this->months,
        ])->layout('layouts.tenant', ['title' => 'Formularios Tributarios']);
    }
}
