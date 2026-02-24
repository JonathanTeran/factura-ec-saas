<?php

namespace App\Jobs\SRI;

use App\Models\SRI\ElectronicDocument;
use App\Services\SRI\SRIService;
use App\Events\DocumentAuthorized;
use App\Events\DocumentRejected;
use App\Events\DocumentFailed;
use App\Enums\DocumentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->queue = 'sri-send';
    }

    /**
     * Evitar procesamiento duplicado del mismo documento
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->document->id))
                ->releaseAfter(120)
                ->expireAfter(300),
        ];
    }

    public function handle(SRIService $sriService): void
    {
        Log::info("Procesando documento #{$this->document->id}");

        $company = $this->document->company;
        $checklist = $company?->emissionReadinessChecklist() ?? [];

        if (empty($checklist['basic_data'])) {
            $this->document->update([
                'status' => DocumentStatus::FAILED,
                'sri_errors' => ['validation' => 'La empresa no tiene completos los datos fiscales del emisor.'],
            ]);
            Log::warning("Documento #{$this->document->id} no procesado: datos fiscales incompletos");
            return;
        }

        if (empty($checklist['establishments'])) {
            $this->document->update([
                'status' => DocumentStatus::FAILED,
                'sri_errors' => ['validation' => 'No existe un establecimiento/punto de emisión activo para el emisor.'],
            ]);
            Log::warning("Documento #{$this->document->id} no procesado: establecimiento/punto de emisión no configurado");
            return;
        }

        if (empty($checklist['sri_password'])) {
            $this->document->update([
                'status' => DocumentStatus::FAILED,
                'sri_errors' => ['validation' => 'La empresa no tiene configurada la clave del SRI.'],
            ]);
            Log::warning("Documento #{$this->document->id} no procesado: clave SRI no configurada");
            return;
        }

        if (empty($checklist['digital_signature'])) {
            $this->document->update([
                'status' => DocumentStatus::FAILED,
                'sri_errors' => ['validation' => 'La empresa no tiene una firma electrónica .p12 válida.'],
            ]);
            Log::warning("Documento #{$this->document->id} no procesado: firma electrónica no válida");
            return;
        }

        try {
            $result = $sriService->process($this->document);
            $this->document->refresh();

            if ($this->document->status === DocumentStatus::AUTHORIZED) {
                Log::info("Documento #{$this->document->id} autorizado");
                event(new DocumentAuthorized($this->document));

                // Incrementar contador del tenant
                $this->document->tenant->increment('documents_this_month');

                // Disparar envío al cliente
                SendDocumentToClientJob::dispatch($this->document)->onQueue('email');
            } else {
                Log::warning("Documento #{$this->document->id} rechazado", [
                    'errors' => $this->document->sri_errors,
                ]);
                event(new DocumentRejected($this->document));
            }
        } catch (\Exception $e) {
            Log::error("Error procesando documento #{$this->document->id}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Documento #{$this->document->id} falló permanentemente", [
            'error' => $e->getMessage(),
        ]);

        $this->document->update([
            'status' => DocumentStatus::FAILED,
            'sri_errors' => ['fatal' => $e->getMessage()],
        ]);

        event(new DocumentFailed($this->document));
    }

    /**
     * Tiempo máximo de reintento
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }
}
