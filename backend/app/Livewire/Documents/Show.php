<?php

namespace App\Livewire\Documents;

use App\Jobs\SRI\ProcessDocumentJob;
use App\Jobs\SRI\SendDocumentToClientJob;
use App\Models\SRI\ElectronicDocument;
use Livewire\Component;

class Show extends Component
{
    public ElectronicDocument $document;

    public function mount(ElectronicDocument $document): void
    {
        // Ensure user has access to this document
        if ($document->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $this->document = $document->load(['customer', 'company', 'items.product']);
    }

    public function sendToSRI(): void
    {
        if (!$this->document->status->isEditable()) {
            session()->flash('error', 'Este documento no puede ser enviado.');
            return;
        }

        if (!$this->document->company->hasValidSignature()) {
            session()->flash('error', 'La empresa no tiene una firma electrónica válida.');
            return;
        }

        $this->document->update(['status' => 'processing']);
        ProcessDocumentJob::dispatch($this->document);

        session()->flash('success', 'El documento ha sido enviado a procesar.');
    }

    public function resendEmail(?string $email = null): void
    {
        $email = $email ?? $this->document->customer?->email;

        if (!$email) {
            session()->flash('error', 'No se ha especificado un correo electrónico.');
            return;
        }

        SendDocumentToClientJob::dispatch($this->document, $email);

        session()->flash('success', "El documento será enviado a {$email}");
    }

    public function render()
    {
        return view('livewire.documents.show')
            ->layout('layouts.tenant', ['title' => 'Documento ' . $this->document->document_number]);
    }
}
