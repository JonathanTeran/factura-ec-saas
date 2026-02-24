<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Events\DocumentSigned;
use App\Exceptions\CertificateException;
use App\Exceptions\SignatureException;
use App\Models\SRI\ElectronicDocument;
use App\Services\SRI\SigningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SignDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 10;

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->onQueue('documents');
    }

    public function handle(SigningService $signingService): void
    {
        Log::info("Signing document {$this->document->id}");

        try {
            // Generate XML
            $xml = $signingService->generateXml($this->document);

            // Sign XML with electronic signature
            $signedXml = $signingService->signXml($xml, $this->document->company);

            // Generate access key
            $accessKey = $signingService->generateAccessKey($this->document);

            // Store XML file
            $xmlPath = "documents/{$this->document->tenant_id}/{$this->document->company_id}/" .
                now()->format('Y/m') . "/{$accessKey}.xml";

            \Storage::put($xmlPath, $signedXml);

            // Update document
            $this->document->update([
                'access_key' => $accessKey,
                'xml_signed_path' => $xmlPath,
                'status' => DocumentStatus::SIGNED,
            ]);

            event(new DocumentSigned($this->document));

            Log::info("Document {$this->document->id} signed successfully");

            // Automatically dispatch to SRI
            SendDocumentToSriJob::dispatch($this->document);

        } catch (CertificateException $e) {
            Log::error("Certificate error signing document {$this->document->id}", $e->context());

            $this->document->update([
                'status' => DocumentStatus::DRAFT,
                'notes' => "Error de certificado: {$e->getMessage()}",
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::error("Error signing document {$this->document->id}: {$e->getMessage()}");

            $this->document->update([
                'status' => DocumentStatus::DRAFT,
                'notes' => "Error al firmar: {$e->getMessage()}",
            ]);

            throw new SignatureException("Error al firmar documento: {$e->getMessage()}", $this->document->company_id, $this->document->id, $e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SignDocumentJob failed for document {$this->document->id}: {$exception->getMessage()}");

        $this->document->update([
            'status' => DocumentStatus::DRAFT,
            'notes' => "Error al firmar (falló después de {$this->tries} intentos): {$exception->getMessage()}",
        ]);
    }
}
