<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\JournalEntryStatus;
use App\Models\Accounting\JournalEntry;
use App\Models\Tenant\Company;
use App\Services\Accounting\AccountingService;
use Livewire\Component;

class JournalEntryShow extends Component
{
    public JournalEntry $entry;
    public bool $showVoidModal = false;
    public string $voidReason = '';

    public function mount(JournalEntry $entry): void
    {
        abort_unless($entry->tenant_id === auth()->user()->tenant_id, 403);

        $this->entry = $entry->load([
            'lines.account',
            'company',
            'fiscalPeriod',
            'createdByUser',
            'postedByUser',
            'voidedByUser',
        ]);
    }

    public function postEntry(AccountingService $service): void
    {
        $company = Company::findOrFail($this->entry->company_id);

        try {
            $this->entry = $service->forCompany($company)->postJournalEntry($this->entry);
            $this->entry->load([
                'lines.account',
                'company',
                'fiscalPeriod',
                'createdByUser',
                'postedByUser',
                'voidedByUser',
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Asiento contabilizado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openVoidModal(): void
    {
        $this->showVoidModal = true;
        $this->voidReason = '';
    }

    public function closeVoidModal(): void
    {
        $this->showVoidModal = false;
        $this->voidReason = '';
    }

    public function voidEntry(AccountingService $service): void
    {
        $this->validate([
            'voidReason' => 'required|string|min:10|max:500',
        ]);

        $company = Company::findOrFail($this->entry->company_id);

        try {
            $this->entry = $service->forCompany($company)->voidJournalEntry($this->entry, $this->voidReason);
            $this->entry->load([
                'lines.account',
                'company',
                'fiscalPeriod',
                'createdByUser',
                'postedByUser',
                'voidedByUser',
            ]);

            $this->showVoidModal = false;
            $this->voidReason = '';

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Asiento anulado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.panel.accounting.journal-entry-show')
            ->layout('layouts.tenant', ['title' => "Asiento #{$this->entry->entry_number}"]);
    }
}
