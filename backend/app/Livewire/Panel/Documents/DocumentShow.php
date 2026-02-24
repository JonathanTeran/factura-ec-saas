<?php

namespace App\Livewire\Panel\Documents;

use App\Enums\DocumentStatus;
use App\Models\SRI\ElectronicDocument;
use App\Services\Notification\NotificationService;
use Livewire\Component;

class DocumentShow extends Component
{
    public ElectronicDocument $document;

    public function mount(ElectronicDocument $document): void
    {
        abort_unless($document->tenant_id === auth()->user()->tenant_id, 403);

        $this->document = $document->load([
            'customer',
            'emissionPoint',
            'items.product',
            'payments',
        ]);
    }

    public function downloadPdf(): void
    {
        $this->dispatch('download-file', [
            'url' => route('documents.download.pdf', $this->document),
            'filename' => "documento-{$this->document->sequential}.pdf",
        ]);
    }

    public function downloadXml(): void
    {
        $this->dispatch('download-file', [
            'url' => route('documents.download.xml', $this->document),
            'filename' => "documento-{$this->document->sequential}.xml",
        ]);
    }

    public function sendEmail(): void
    {
        // Dispatch job to send email
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'El documento se enviará por correo electrónico.',
        ]);
    }

    public function sendWhatsApp(): void
    {
        if (!$this->document->ride_pdf_path) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El documento aún no tiene RIDE generado.',
            ]);
            return;
        }

        $customer = $this->document->customer;

        if (!$customer || !$customer->phone) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El cliente no tiene número de teléfono registrado.',
            ]);
            return;
        }

        try {
            $notificationService = app(NotificationService::class);
            $sent = $notificationService->sendDocumentByWhatsApp($this->document);

            if ($sent) {
                $this->document->refresh();
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Documento enviado por WhatsApp correctamente.',
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'No se pudo enviar el documento por WhatsApp.',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al enviar por WhatsApp: ' . $e->getMessage(),
            ]);
        }
    }

    public function resendToSri(): void
    {
        if (!in_array($this->document->status, [DocumentStatus::DRAFT, DocumentStatus::REJECTED])) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Este documento no puede ser reenviado al SRI.',
            ]);
            return;
        }

        // Dispatch job to resend
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'El documento será procesado nuevamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.documents.document-show')
            ->layout('layouts.tenant', ['title' => "Documento #{$this->document->sequential}"]);
    }
}
