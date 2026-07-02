<?php

namespace App\Livewire\Panel\Guides;

use App\Enums\DocumentStatus;
use App\Models\SRI\ElectronicDocument;
use Livewire\Component;
use Livewire\WithPagination;

class GuideList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'issue_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
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
        $this->reset(['search', 'status', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getGuidesProperty()
    {
        $query = ElectronicDocument::where('tenant_id', auth()->user()->tenant_id)
            ->where('document_type', '06')
            ->with(['emissionPoint.branch.company']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sequential_number', 'like', "%{$this->search}%")
                    ->orWhere('destination_name', 'like', "%{$this->search}%")
                    ->orWhere('destination_ruc', 'like', "%{$this->search}%")
                    ->orWhere('carrier_plate', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom) {
            $query->where('issue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('issue_date', '<=', $this->dateTo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId  = auth()->user()->tenant_id;
        $thisMonth = now()->startOfMonth();

        return [
            'total'      => ElectronicDocument::where('tenant_id', $tenantId)->where('document_type', '06')->count(),
            'this_month' => ElectronicDocument::where('tenant_id', $tenantId)->where('document_type', '06')->where('issue_date', '>=', $thisMonth)->count(),
            'authorized' => ElectronicDocument::where('tenant_id', $tenantId)->where('document_type', '06')->where('status', DocumentStatus::AUTHORIZED)->count(),
            'pending'    => ElectronicDocument::where('tenant_id', $tenantId)->where('document_type', '06')->whereIn('status', [DocumentStatus::DRAFT, DocumentStatus::SIGNED])->count(),
        ];
    }

    public function render()
    {
        return view('livewire.panel.guides.guide-list', [
            'guides'   => $this->guides,
            'stats'    => $this->stats,
            'statuses' => DocumentStatus::cases(),
        ])->layout('layouts.tenant', ['title' => 'Guías de Remisión']);
    }
}
