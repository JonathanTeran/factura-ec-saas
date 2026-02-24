<?php

namespace App\Livewire\Portal;

use App\Enums\DocumentType;
use App\Services\Portal\CustomerPortalService;
use Livewire\Component;
use Livewire\WithPagination;

class PortalDocumentList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'issue_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
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
        $this->reset(['search', 'type', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $session = request()->attributes->get('portal_session');
        $service = app(CustomerPortalService::class);

        $documents = $service->getDocumentsForSession($session, [
            'search' => $this->search,
            'type' => $this->type,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
        ], config('portal.documents_per_page', 15));

        return view('livewire.portal.portal-document-list', [
            'documents' => $documents,
            'documentTypes' => DocumentType::cases(),
        ])->layout('layouts.portal', ['title' => 'Mis Documentos']);
    }
}
