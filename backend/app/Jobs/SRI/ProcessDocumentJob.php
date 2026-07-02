<?php

namespace App\Jobs\SRI;

use App\Enums\DocumentStatus;
use App\Events\DocumentAuthorized;
use App\Events\DocumentFailed;
use App\Events\DocumentRejected;
use App\Exceptions\SriCommunicationException;
use App\Models\SRI\ElectronicDocument;
use App\Services\SRI\SRIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
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
        $this->queue = 'sri';
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
                SendDocumentToClientJob::dispatch($this->document)->onQueue('emails');
            } else {
                Log::warning("Documento #{$this->document->id} rechazado", [
                    'errors' => $this->document->sri_errors,
                ]);
                event(new DocumentRejected($this->document));
            }
        } catch (SriCommunicationException $e) {
            Log::warning("Documento #{$this->document->id} en contingencia SRI", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
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

        if ($e instanceof SriCommunicationException) {
            $this->document->update([
                'status' => DocumentStatus::PROCESSING,
                'sri_errors' => array_merge($this->document->sri_errors ?? [], [
                    'contingency_active' => true,
                    'contingency_message' => $e->getMessage(),
                    'retry_recommended_at' => now()->addMinutes(15)->toIso8601String(),
                ]),
                'last_sri_attempt_at' => now(),
            ]);

            return;
        }

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
        return now()->addMinutes(20);
    }
}
