<?php

namespace App\Notifications;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ElectronicDocument $document,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Documento Rechazado - {$this->document->document_number}")
            ->view('emails.document-rejected', [
                'document' => $this->document,
                'company' => $this->document->company,
                'user' => $notifiable,
                'errors' => $this->document->sri_errors ?? ($this->reason ? [$this->reason] : []),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_rejected',
            'document_id' => $this->document->id,
            'document_number' => $this->document->document_number,
            'document_type' => $this->document->document_type,
            'reason' => $this->reason,
            'customer_name' => $this->document->customer->business_name,
            'message' => "Documento {$this->document->document_number} rechazado",
        ];
    }
}
