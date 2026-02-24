<?php

namespace App\Livewire\Panel\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use App\Services\Accounting\BudgetService;
use Livewire\Component;

class BudgetForm extends Component
{
    public ?Budget $budget = null;

    public string $name = '';
    public int $year;
    public string $notes = '';
    public array $lines = [];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'year' => 'required|integer|min:2020|max:2099',
            'notes' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|integer|exists:chart_of_accounts,id',
            'lines.*.month' => 'required|integer|min:1|max:12',
            'lines.*.budgeted_amount' => 'required|numeric|min:0.01',
        ];
    }

    protected $messages = [
        'lines.required' => 'Agrega al menos una linea al presupuesto.',
        'lines.min' => 'Agrega al menos una linea al presupuesto.',
        'lines.*.account_id.required' => 'Selecciona una cuenta.',
        'lines.*.month.required' => 'Selecciona un mes.',
        'lines.*.budgeted_amount.required' => 'Ingresa el monto presupuestado.',
        'lines.*.budgeted_amount.min' => 'El monto debe ser mayor a cero.',
    ];

    public function mount(?Budget $budget = null): void
    {
        $this->year = now()->year;

        if ($budget && $budget->exists) {
            $this->budget = $budget;
            $this->name = $budget->name;
            $this->year = $budget->year;
            $this->notes = $budget->notes ?? '';

            $this->lines = $budget->lines->map(function ($line) {
                return [
                    'account_id' => $line->account_id,
                    'month' => $line->month,
                    'budgeted_amount' => (string) $line->budgeted_amount,
                ];
            })->toArray();
        }

        if (empty($this->lines)) {
            $this->addLine();
        }
    }

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getAccountsProperty()
    {
        $company = $this->company;

        if (!$company) {
            return collect();
        }

        return Account::where('company_id', $company->id)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($y = $currentYear + 1; $y >= $currentYear - 3; $y--) {
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

    public function getTotalAmountProperty(): float
    {
        return collect($this->lines)->sum(function ($line) {
            return (float) ($line['budgeted_amount'] ?? 0);
        });
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'account_id' => '',
            'month' => now()->month,
            'budgeted_amount' => '',
        ];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 1) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Debe haber al menos una linea.',
            ]);
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function duplicateLine(int $index): void
    {
        $line = $this->lines[$index];
        $newLine = $line;

        // Incrementar mes si es posible
        if ((int) $newLine['month'] < 12) {
            $newLine['month'] = (int) $newLine['month'] + 1;
        }

        array_splice($this->lines, $index + 1, 0, [$newLine]);
    }

    public function save(): void
    {
        $this->validate();

        $company = $this->company;

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        $service = app(BudgetService::class)->forCompany($company);

        $data = [
            'name' => $this->name,
            'year' => $this->year,
            'notes' => $this->notes ?: null,
        ];

        $lines = collect($this->lines)->map(function ($line) {
            return [
                'account_id' => (int) $line['account_id'],
                'month' => (int) $line['month'],
                'budgeted_amount' => (float) $line['budgeted_amount'],
            ];
        })->toArray();

        try {
            if ($this->budget) {
                $service->updateBudget($this->budget, $data, $lines);
                $message = 'Presupuesto actualizado correctamente.';
            } else {
                $service->createBudget($data, $lines);
                $message = 'Presupuesto creado correctamente.';
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message,
            ]);

            $this->redirect(route('panel.accounting.budgets'), navigate: true);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.panel.accounting.budget-form', [
            'accounts' => $this->accounts,
            'years' => $this->years,
            'months' => $this->months,
            'totalAmount' => $this->totalAmount,
        ])->layout('layouts.tenant', [
            'title' => $this->budget ? 'Editar Presupuesto' : 'Nuevo Presupuesto',
        ]);
    }
}
