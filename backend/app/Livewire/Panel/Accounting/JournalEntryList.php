<?php

namespace App\Livewire\Panel\Accounting;

use App\Enums\JournalEntrySource;
use App\Enums\JournalEntryStatus;
use App\Models\Accounting\JournalEntry;
use Livewire\Component;
use Livewire\WithPagination;

class JournalEntryList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $source = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'entry_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'source' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'source', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getEntriesProperty()
    {
        $query = JournalEntry::where('tenant_id', auth()->user()->tenant_id)
            ->with(['company', 'createdByUser']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('entry_number', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->source) {
            $query->where('source_type', $this->source);
        }

        if ($this->dateFrom) {
            $query->where('entry_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('entry_date', '<=', $this->dateTo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $thisMonth = now()->startOfMonth();

        return [
            'total' => JournalEntry::where('tenant_id', $tenantId)->count(),
            'this_month' => JournalEntry::where('tenant_id', $tenantId)
                ->where('entry_date', '>=', $thisMonth)
                ->count(),
            'draft' => JournalEntry::where('tenant_id', $tenantId)
                ->where('status', JournalEntryStatus::DRAFT)
                ->count(),
            'posted_month' => JournalEntry::where('tenant_id', $tenantId)
                ->where('status', JournalEntryStatus::POSTED)
                ->where('entry_date', '>=', $thisMonth)
                ->count(),
        ];
    }

    public function render()
    {
        return view('livewire.panel.accounting.journal-entry-list', [
            'entries' => $this->entries,
            'stats' => $this->stats,
            'statuses' => JournalEntryStatus::cases(),
            'sources' => JournalEntrySource::cases(),
        ])->layout('layouts.tenant', ['title' => 'Asientos Contables']);
    }
}
