<?php

namespace App\Jobs\SRI;

use App\Models\SRI\ElectronicDocument;
use App\Mail\DocumentAuthorizedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendDocumentToClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->queue = 'email';
    }

    public function handle(): void
    {
        $customer = $this->document->customer;

        if (!$customer || !$customer->email) {
            Log::info("Documento #{$this->document->id}: cliente sin email, omitiendo envío");
            return;
        }

        try {
            // Obtener archivos adjuntos
            $attachments = [];

            if ($this->document->ride_pdf_path) {
                $attachments[] = [
                    'path' => $this->document->ride_pdf_path,
                    'as' => $this->document->getDocumentNumber() . '.pdf',
                    'mime' => 'application/pdf',
                ];
            }

            if ($this->document->xml_authorized_path) {
                $attachments[] = [
                    'path' => $this->document->xml_authorized_path,
                    'as' => $this->document->getDocumentNumber() . '.xml',
                    'mime' => 'application/xml',
                ];
            }

            // Enviar email
            Mail::to($customer->email)->send(
                new DocumentAuthorizedMail($this->document, $attachments)
            );

            // Actualizar registro
            $this->document->update([
                'email_sent' => true,
                'email_sent_at' => now(),
            ]);

            Log::info("Documento #{$this->document->id} enviado por email a {$customer->email}");

        } catch (\Exception $e) {
            Log::error("Error enviando documento #{$this->document->id} por email", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Envío de documento #{$this->document->id} falló permanentemente", [
            'error' => $e->getMessage(),
        ]);
    }
}
