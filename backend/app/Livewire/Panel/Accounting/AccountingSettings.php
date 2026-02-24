<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\AccountingStandard;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use Livewire\Component;

class AccountingSettings extends Component
{
    public string $accounting_standard = '';
    public bool $auto_journal_entries = false;
    public bool $cost_centers_enabled = false;
    public bool $budgets_enabled = false;

    public function mount(): void
    {
        $company = $this->company;

        if (!$company) {
            return;
        }

        $setting = AccountingSetting::where('company_id', $company->id)->first();

        if ($setting) {
            $this->accounting_standard = $setting->accounting_standard?->value ?? AccountingStandard::NIIF_PYMES->value;
            $this->auto_journal_entries = $setting->auto_journal_entries;
            $this->cost_centers_enabled = $setting->cost_centers_enabled;
            $this->budgets_enabled = $setting->budgets_enabled;
        } else {
            $this->accounting_standard = AccountingStandard::NIIF_PYMES->value;
        }
    }

    public function getCompanyProperty()
    {
        return auth()->user()->tenant->companies()->first();
    }

    public function getHasAccountsProperty(): bool
    {
        $company = $this->company;

        if (!$company) {
            return false;
        }

        return Account::where('company_id', $company->id)->exists();
    }

    public function getStandardsProperty(): array
    {
        return collect(AccountingStandard::cases())->map(function ($standard) {
            return [
                'value' => $standard->value,
                'label' => $standard->label(),
            ];
        })->toArray();
    }

    public function getSettingProperty(): ?AccountingSetting
    {
        $company = $this->company;

        if (!$company) {
            return null;
        }

        return AccountingSetting::where('company_id', $company->id)->first();
    }

    public function save(): void
    {
        $this->validate([
            'accounting_standard' => 'required|string|in:' . implode(',', array_column(AccountingStandard::cases(), 'value')),
            'auto_journal_entries' => 'boolean',
            'cost_centers_enabled' => 'boolean',
            'budgets_enabled' => 'boolean',
        ]);

        $company = $this->company;

        if (!$company) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se encontro una empresa configurada.',
            ]);
            return;
        }

        // Si ya existen cuentas, no permitir cambiar el estandar contable
        if ($this->hasAccounts) {
            $existingSetting = $this->setting;
            if ($existingSetting && $existingSetting->accounting_standard?->value !== $this->accounting_standard) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'No se puede cambiar el estandar contable porque ya existen cuentas creadas.',
                ]);
                $this->accounting_standard = $existingSetting->accounting_standard->value;
                return;
            }
        }

        AccountingSetting::updateOrCreate(
            [
                'tenant_id' => auth()->user()->tenant_id,
                'company_id' => $company->id,
            ],
            [
                'accounting_standard' => $this->accounting_standard,
                'auto_journal_entries' => $this->auto_journal_entries,
                'cost_centers_enabled' => $this->cost_centers_enabled,
                'budgets_enabled' => $this->budgets_enabled,
            ]
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Configuracion contable guardada correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.accounting.accounting-settings', [
            'standards' => $this->standards,
            'hasAccounts' => $this->hasAccounts,
        ])->layout('layouts.tenant', ['title' => 'Configuracion Contable']);
    }
}
