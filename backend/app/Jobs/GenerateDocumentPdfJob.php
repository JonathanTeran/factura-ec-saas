<?php

namespace App\Jobs;

use App\Models\SRI\ElectronicDocument;
use App\Services\Documents\PdfService;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateDocumentPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public ElectronicDocument $document
    ) {
        $this->onQueue('documents');
    }

    public function handle(PdfService $pdfService): void
    {
        Log::info("Generating PDF for document {$this->document->id}");

        try {
            // Generate PDF content
            $pdfContent = $pdfService->generate($this->document);

            // Define storage path
            $pdfPath = "documents/{$this->document->tenant_id}/{$this->document->company_id}/" .
                now()->format('Y/m') . "/{$this->document->access_key}.pdf";

            // Store PDF
            Storage::put($pdfPath, $pdfContent);

            // Update document
            $this->document->update(['ride_pdf_path' => $pdfPath]);

            Log::info("PDF generated for document {$this->document->id}");

            // Auto-send via WhatsApp if enabled for the tenant
            $this->sendWhatsAppIfEnabled();

        } catch (\Exception $e) {
            Log::error("Error generating PDF for document {$this->document->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Send RIDE via WhatsApp if auto-send is enabled for the tenant's company.
     */
    protected function sendWhatsAppIfEnabled(): void
    {
        try {
            $company = $this->document->company;

            if (!$company) {
                return;
            }

            $settings = $company->settings ?? [];
            $whatsappEnabled = (bool) ($settings['whatsapp_enabled'] ?? false);
            $whatsappAutoSend = (bool) ($settings['whatsapp_auto_send'] ?? false);

            if (!$whatsappEnabled || !$whatsappAutoSend) {
                return;
            }

            $customer = $this->document->customer;

            if (!$customer || !$customer->phone) {
                Log::info("WhatsApp auto-send skipped for document {$this->document->id}: customer has no phone");
                return;
            }

            $notificationService = app(NotificationService::class);
            $sent = $notificationService->sendDocumentByWhatsApp($this->document);

            if ($sent) {
                Log::info("WhatsApp auto-send completed for document {$this->document->id}");
            } else {
                Log::warning("WhatsApp auto-send failed for document {$this->document->id}");
            }
        } catch (\Exception $e) {
            // Don't re-throw: WhatsApp failure should not fail the PDF generation job
            Log::error("WhatsApp auto-send error for document {$this->document->id}: {$e->getMessage()}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateDocumentPdfJob failed for document {$this->document->id}: {$exception->getMessage()}");
    }
}
