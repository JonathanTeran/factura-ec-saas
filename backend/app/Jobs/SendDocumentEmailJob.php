<?php

namespace App\Jobs;

use App\Models\SRI\ElectronicDocument;
use App\Notifications\DocumentEmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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

        if (!$email) {
            Log::warning("No email address for document {$this->document->id}");
            return;
        }

        Log::info("Sending document {$this->document->id} to {$email}");

        try {
            Notification::route('mail', $email)
                ->notify(new DocumentEmailNotification($this->document, $this->customMessage));

            // Update document with email sent timestamp
            $this->document->update([
                'email_sent' => true,
                'email_sent_at' => now(),
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
