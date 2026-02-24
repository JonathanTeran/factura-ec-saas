<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\FiscalPeriodStatus;
use App\Enums\JournalEntryStatus;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\JournalEntry;
use App\Models\Tenant\Company;
use Livewire\Component;

class AccountingDashboard extends Component
{
    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $thisMonth = now()->startOfMonth();

        return [
            'total_accounts' => Account::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count(),
            'entries_this_month' => JournalEntry::where('tenant_id', $tenantId)
                ->where('entry_date', '>=', $thisMonth)
                ->count(),
            'posted_entries' => JournalEntry::where('tenant_id', $tenantId)
                ->where('status', JournalEntryStatus::POSTED)
                ->where('entry_date', '>=', $thisMonth)
                ->count(),
            'open_periods' => FiscalPeriod::where('tenant_id', $tenantId)
                ->where('status', FiscalPeriodStatus::OPEN)
                ->where('period_type', 'monthly')
                ->count(),
        ];
    }

    public function getRecentEntriesProperty()
    {
        return JournalEntry::where('tenant_id', auth()->user()->tenant_id)
            ->with(['createdByUser', 'company'])
            ->latest('entry_date')
            ->latest('id')
            ->take(10)
            ->get();
    }

    public function getIsConfiguredProperty(): bool
    {
        return AccountingSetting::where('tenant_id', auth()->user()->tenant_id)->exists();
    }

    public function render()
    {
        return view('livewire.panel.accounting.accounting-dashboard', [
            'stats' => $this->stats,
            'recentEntries' => $this->recentEntries,
            'isConfigured' => $this->isConfigured,
        ])->layout('layouts.tenant', ['title' => 'Contabilidad']);
    }
}
