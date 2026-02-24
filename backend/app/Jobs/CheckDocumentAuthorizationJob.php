<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Events\DocumentAuthorized;
use App\Events\DocumentRejected;
use App\Exceptions\SriCommunicationException;
use App\Models\SRI\ElectronicDocument;
use App\Services\SRI\SriService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDocumentAuthorizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 60;
    public array $backoff = [5, 10, 30, 60, 120, 180, 300, 600, 900, 1800];

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->onQueue('sri');
    }

    public function handle(SriService $sriService): void
    {
        Log::info("Checking authorization for document {$this->document->id}");

        // Only check if document is in SENT status
        if (!in_array($this->document->status, [DocumentStatus::SENT, DocumentStatus::PROCESSING])) {
            Log::warning("Document {$this->document->id} is not in SENT/PROCESSING status, skipping");
            return;
        }

        try {
            $authResponse = $sriService->checkAuthorization($this->document->access_key);

            if ($authResponse['status'] === 'AUTORIZADO') {
                $this->handleAuthorization($authResponse);
            } elseif ($authResponse['status'] === 'RECHAZADO') {
                $this->handleRejection($authResponse);
            } else {
                // Still pending, check again later
                Log::info("Document {$this->document->id} still pending authorization");

                $this->document->update(['status' => DocumentStatus::PROCESSING]);

                // Re-dispatch with delay if we haven't exceeded max attempts
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 1800);
                }
            }

        } catch (SriCommunicationException $e) {
            Log::error("SRI communication error checking document {$this->document->id}", $e->context());
            throw $e; // Retry with backoff
        } catch (\Exception $e) {
            Log::error("Error checking authorization for document {$this->document->id}: {$e->getMessage()}");

            if ($this->attempts() >= $this->tries) {
                $this->document->update([
                    'status' => DocumentStatus::FAILED,
                    'sri_errors' => ['message' => "No se pudo verificar la autorizacion: {$e->getMessage()}"],
                ]);
            }

            throw new SriCommunicationException("Error consultando autorizacion: {$e->getMessage()}", $this->document->id, $this->document->access_key, 0, $e);
        }
    }

    protected function handleAuthorization(array $response): void
    {
        Log::info("Document {$this->document->id} authorized by SRI");

        $this->document->update([
            'status' => DocumentStatus::AUTHORIZED,
            'authorization_number' => $response['authorization_number'] ?? $this->document->access_key,
            'authorization_date' => $response['authorization_date'] ?? now(),
            'sri_response' => $response,
        ]);

        // Generate PDF
        GenerateDocumentPdfJob::dispatch($this->document);

        // Fire authorized event
        event(new DocumentAuthorized($this->document));
    }

    protected function handleRejection(array $response): void
    {
        Log::warning("Document {$this->document->id} rejected by SRI", $response);

        $this->document->update([
            'status' => DocumentStatus::REJECTED,
            'sri_errors' => $response['errors'] ?? [],
            'sri_response' => $response,
        ]);

        // Fire rejection event
        event(new DocumentRejected(
            $this->document,
            $response['errors'][0]['mensaje'] ?? 'Error desconocido'
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CheckDocumentAuthorizationJob failed for document {$this->document->id}: {$exception->getMessage()}");

        $this->document->update([
            'status' => DocumentStatus::FAILED,
            'sri_errors' => ['message' => "Error verificando autorización: {$exception->getMessage()}"],
        ]);
    }
}
