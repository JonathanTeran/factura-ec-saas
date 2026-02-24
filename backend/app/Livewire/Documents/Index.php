<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\SRI\ElectronicDocument;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $documentType = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'documentType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'documentType', 'dateFrom', 'dateTo']);
    }

    public function render()
    {
        $query = ElectronicDocument::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer:id,name,identification_number', 'company:id,trade_name']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('access_key', 'like', "%{$this->search}%")
                    ->orWhere('document_number', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                            ->orWhere('identification_number', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->documentType) {
            $query->where('document_type', $this->documentType);
        }

        if ($this->dateFrom) {
            $query->whereDate('issue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('issue_date', '<=', $this->dateTo);
        }

        $documents = $query->orderByDesc('issue_date')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.documents.index', [
            'documents' => $documents,
            'statuses' => DocumentStatus::cases(),
            'documentTypes' => DocumentType::cases(),
        ])->layout('layouts.tenant', ['title' => 'Documentos']);
    }
}
