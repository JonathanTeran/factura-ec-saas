<?php

namespace App\Mail;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DocumentAuthorizedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ElectronicDocument $document,
        public array $attachments = [],
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->document->company;
        $typeName = $this->document->document_type->label();
        $number = $this->document->getDocumentNumber();

        return new Envelope(
            subject: "{$typeName} #{$number} - {$company->business_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-authorized',
            with: [
                'document' => $this->document,
                'company' => $this->document->company,
                'customer' => $this->document->customer,
                'items' => $this->document->items,
                'portalUrl' => route('portal.login'),
            ],
        );
    }

    public function attachments(): array
    {
        $mailAttachments = [];

        foreach ($this->attachments as $attachment) {
            if (Storage::exists($attachment['path'])) {
                $mailAttachments[] = \Illuminate\Mail\Mailables\Attachment::fromStorage($attachment['path'])
                    ->as($attachment['as'])
                    ->withMime($attachment['mime']);
            }
        }

        return $mailAttachments;
    }
}
