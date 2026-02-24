<?php

namespace App\Livewire\Portal;

use App\Models\SRI\ElectronicDocument;
use App\Services\Portal\CustomerPortalService;
use Livewire\Component;

class PortalDocumentShow extends Component
{
    public ?ElectronicDocument $document = null;

    public function mount(int|ElectronicDocument $document): void
    {
        $session = request()->attributes->get('portal_session');
        $service = app(CustomerPortalService::class);

        $documentId = $document instanceof ElectronicDocument ? $document->id : $document;
        $this->document = $service->getDocument($session, $documentId);

        if (!$this->document) {
            abort(404);
        }
    }

    public function render()
    {
        $title = $this->document
            ? $this->document->document_type->label() . ' ' . $this->document->getDocumentNumber()
            : 'Documento';

        return view('livewire.portal.portal-document-show')
            ->layout('layouts.portal', ['title' => $title]);
    }
}
