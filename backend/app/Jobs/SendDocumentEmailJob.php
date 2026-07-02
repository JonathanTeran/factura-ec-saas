<?php

namespace App\Jobs;

use App\Mail\DocumentAuthorizedMail;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDocumentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        public ElectronicDocument $document,
        public ?string $email = null,
        public ?string $customMessage = null
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $email = $this->email ?? $this->document->customer->email;

        if (! $email) {
            Log::warning("No email address for document {$this->document->id}");

            return;
        }

        Log::info("Sending document {$this->document->id} to {$email}");

        // Correos adicionales del cliente (copia), excluyendo al destinatario principal
        $cc = collect($this->document->customer?->additional_emails ?? [])
            ->filter(fn ($e) => is_string($e) && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->reject(fn ($e) => strcasecmp($e, $email) === 0)
            ->unique(fn ($e) => strtolower($e))
            ->values()
            ->all();

        try {
            $mail = Mail::to($email);

            if (! empty($cc)) {
                $mail->cc($cc);
            }

            $mail->send(new DocumentAuthorizedMail($this->document));

            // Update document with email sent timestamp + recipient
            $this->document->update([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_sent_to' => $email,
            ]);

            Log::info("Document {$this->document->id} sent to {$email}");

        } catch (\Exception $e) {
            Log::error("Error sending document {$this->document->id} email: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendDocumentEmailJob failed for document {$this->document->id}: {$exception->getMessage()}");
    }
}
