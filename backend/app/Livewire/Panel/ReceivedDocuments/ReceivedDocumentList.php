<?php

namespace App\Livewire\Panel\ReceivedDocuments;

use App\Enums\ExpenseCategory;
use App\Models\Tenant\ReceivedDocument;
use Livewire\Component;
use Livewire\WithPagination;

class ReceivedDocumentList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $category = '';
    public string $isProcessed = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'issue_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search'      => ['except' => ''],
        'category'    => ['except' => ''],
        'isProcessed' => ['except' => ''],
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
        $this->reset(['search', 'category', 'isProcessed', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getDocumentsProperty()
    {
        $query = ReceivedDocument::where('tenant_id', auth()->user()->tenant_id)
            ->with('company');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('issuer_ruc', 'like', "%{$this->search}%")
                    ->orWhere('issuer_name', 'like', "%{$this->search}%")
                    ->orWhere('authorization_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->category) {
            $query->where('expense_category', $this->category);
        }

        if ($this->isProcessed !== '') {
            $query->where('is_processed', (bool) $this->isProcessed);
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
            'total'              => ReceivedDocument::where('tenant_id', $tenantId)->count(),
            'this_month'         => ReceivedDocument::where('tenant_id', $tenantId)->where('issue_date', '>=', $thisMonth)->count(),
            'total_amount_month' => (float) ReceivedDocument::where('tenant_id', $tenantId)->where('issue_date', '>=', $thisMonth)->sum('total'),
            'unprocessed'        => ReceivedDocument::where('tenant_id', $tenantId)->where('is_processed', false)->count(),
        ];
    }

    public function getCategoriesProperty(): array
    {
        return ExpenseCategory::cases();
    }

    public function markAsProcessed(int $docId): void
    {
        $doc = ReceivedDocument::where('tenant_id', auth()->user()->tenant_id)->findOrFail($docId);
        $doc->update(['is_processed' => !$doc->is_processed]);

        $msg = $doc->is_processed ? 'Documento marcado como procesado.' : 'Documento marcado como no procesado.';
        $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
    }

    public function render()
    {
        return view('livewire.panel.received-documents.received-document-list', [
            'documents'  => $this->documents,
            'stats'      => $this->stats,
            'categories' => $this->categories,
        ])->layout('layouts.tenant', ['title' => 'Documentos Recibidos']);
    }
}
