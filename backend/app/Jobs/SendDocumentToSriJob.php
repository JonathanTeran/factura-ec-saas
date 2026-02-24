<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Exceptions\SriCommunicationException;
use App\Exceptions\SriRejectionException;
use App\Models\SRI\ElectronicDocument;
use App\Services\SRI\SriService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDocumentToSriJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 300;
    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->onQueue('sri');
    }

    public function handle(SriService $sriService): void
    {
        Log::info("Sending document {$this->document->id} to SRI");

        // Check if document is in correct status
        if ($this->document->status !== DocumentStatus::SIGNED) {
            Log::warning("Document {$this->document->id} is not signed, skipping SRI submission");
            return;
        }

        try {
            $this->document->update(['status' => DocumentStatus::SENT]);

            // Send to SRI reception
            $receptionResponse = $sriService->sendToReception($this->document);

            if ($receptionResponse['status'] === 'RECIBIDA') {
                Log::info("Document {$this->document->id} received by SRI, checking authorization");

                // Wait a bit for SRI to process
                sleep(2);

                // Dispatch authorization check
                CheckDocumentAuthorizationJob::dispatch($this->document);
            } else {
                // Document was rejected
                $this->handleRejection($receptionResponse);
            }

        } catch (SriRejectionException $e) {
            Log::warning("Document {$this->document->id} rejected by SRI", $e->context());
            // Rejection is final, don't retry
            $this->fail($e);
        } catch (SriCommunicationException $e) {
            Log::error("SRI communication error for document {$this->document->id}", $e->context());
            throw $e; // Retry
        } catch (\Exception $e) {
            Log::error("Error sending document {$this->document->id} to SRI: {$e->getMessage()}");

            if ($this->attempts() >= $this->tries) {
                $this->document->update([
                    'status' => DocumentStatus::FAILED,
                    'sri_errors' => ['message' => $e->getMessage()],
                ]);
            }

            throw new SriCommunicationException("Error enviando al SRI: {$e->getMessage()}", $this->document->id, $this->document->access_key, 0, $e);
        }
    }

    protected function handleRejection(array $response): void
    {
        Log::warning("Document {$this->document->id} rejected by SRI", $response);

        $this->document->update([
            'status' => DocumentStatus::REJECTED,
            'sri_errors' => $response['errors'] ?? [['mensaje' => 'Documento rechazado por el SRI']],
        ]);

        // Fire rejection event
        event(new \App\Events\DocumentRejected(
            $this->document,
            $response['errors'][0]['mensaje'] ?? 'Error desconocido'
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendDocumentToSriJob failed for document {$this->document->id}: {$exception->getMessage()}");

        $this->document->update([
            'status' => DocumentStatus::FAILED,
            'sri_errors' => ['message' => "Error al enviar al SRI después de {$this->tries} intentos: {$exception->getMessage()}"],
        ]);
    }
}
