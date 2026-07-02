<?php

namespace App\Jobs\SRI;

use App\Mail\DocumentAuthorizedMail;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDocumentToClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public ElectronicDocument $document,
        public ?string $email = null,
    ) {
        $this->queue = 'emails';
    }

    public function handle(): void
    {
        $customer = $this->document->customer;
        $recipientEmail = $this->email ?: $customer?->email;

        if (! $recipientEmail) {
            Log::info("Documento #{$this->document->id}: cliente sin email, omitiendo envío");

            return;
        }

        // Correos adicionales del cliente (copia), excluyendo al destinatario principal
        $cc = collect($customer?->additional_emails ?? [])
            ->filter(fn ($e) => is_string($e) && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->reject(fn ($e) => strcasecmp($e, $recipientEmail) === 0)
            ->unique(fn ($e) => strtolower($e))
            ->values()
            ->all();

        try {
            $mail = Mail::to($recipientEmail);

            if (! empty($cc)) {
                $mail->cc($cc);
            }

            $mail->send(new DocumentAuthorizedMail($this->document));

            // Actualizar registro (incluye a quién se envió)
            $this->document->update([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_sent_to' => $recipientEmail,
            ]);

            Log::info("Documento #{$this->document->id} enviado por email a {$recipientEmail}");

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
