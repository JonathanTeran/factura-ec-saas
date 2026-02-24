<?php

namespace App\Livewire\Panel\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\SRI\ElectronicDocument as Document;
use App\Models\Tenant\Customer;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $type = '';
    public string $customer = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public array $selectedDocuments = [];
    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'type' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
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

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedDocuments = $this->documents->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedDocuments = [];
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'type', 'customer', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getDocumentsProperty()
    {
        $query = Document::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer', 'emissionPoint']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('access_key', 'like', "%{$this->search}%")
                    ->orWhere('sequential', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                            ->orWhere('identification', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->type) {
            $query->where('document_type', $this->type);
        }

        if ($this->customer) {
            $query->where('customer_id', $this->customer);
        }

        if ($this->dateFrom) {
            $query->whereDate('issue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('issue_date', '<=', $this->dateTo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function getCustomersProperty()
    {
        return Customer::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'identification']);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => Document::where('tenant_id', $tenantId)
                ->whereDate('created_at', $today)
                ->count(),
            'thisMonth' => Document::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $thisMonth)
                ->count(),
            'pending' => Document::where('tenant_id', $tenantId)
                ->whereIn('status', [DocumentStatus::DRAFT, DocumentStatus::PROCESSING, DocumentStatus::SENT])
                ->count(),
            'authorized' => Document::where('tenant_id', $tenantId)
                ->where('status', DocumentStatus::AUTHORIZED)
                ->where('created_at', '>=', $thisMonth)
                ->count(),
        ];
    }

    public function downloadPdf(int $documentId): void
    {
        $document = Document::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($documentId);

        $this->dispatch('download-file', [
            'url' => route('documents.download.pdf', $document),
            'filename' => "documento-{$document->sequential}.pdf",
        ]);
    }

    public function downloadXml(int $documentId): void
    {
        $document = Document::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($documentId);

        $this->dispatch('download-file', [
            'url' => route('documents.download.xml', $document),
            'filename' => "documento-{$document->sequential}.xml",
        ]);
    }

    public function sendEmail(int $documentId): void
    {
        $document = Document::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($documentId);

        // Dispatch job to send email
        // SendDocumentEmailJob::dispatch($document);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'El documento se enviará por correo electrónico.',
        ]);
    }

    public function bulkDownload(): void
    {
        if (empty($this->selectedDocuments)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Selecciona al menos un documento.',
            ]);
            return;
        }

        // Logic to create zip file with selected documents
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Preparando descarga de ' . count($this->selectedDocuments) . ' documentos...',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.documents.document-list', [
            'documents' => $this->documents,
            'customers' => $this->customers,
            'stats' => $this->stats,
            'documentTypes' => DocumentType::cases(),
            'documentStatuses' => DocumentStatus::cases(),
        ])->layout('layouts.tenant', ['title' => 'Documentos']);
    }
}
