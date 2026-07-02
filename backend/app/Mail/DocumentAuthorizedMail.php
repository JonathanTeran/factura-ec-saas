<?php

namespace App\Mail;

use App\Models\SRI\ElectronicDocument;
use App\Services\Settings\DocumentEmailTemplateSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
        $templateSettings = app(DocumentEmailTemplateSettings::class);
        $compiled = $templateSettings->compile(
            $templateSettings->placeholdersForDocument($this->document)
        );

        return new Envelope(
            subject: $compiled['subject'],
        );
    }

    public function content(): Content
    {
        $templateSettings = app(DocumentEmailTemplateSettings::class);
        $compiled = $templateSettings->compile(
            $templateSettings->placeholdersForDocument($this->document)
        );

        $allowedTags = '<p><br><strong><b><em><i><u><a><ul><ol><li><h1><h2><h3><span><div><table><tr><td><th>';

        return new Content(
            view: 'emails.document-authorized',
            with: [
                'mailTemplate' => array_merge($compiled, [
                    'body_html'   => strip_tags($compiled['body_html'] ?? '', $allowedTags),
                    'footer_html' => strip_tags($compiled['footer_html'] ?? '', $allowedTags),
                ]),
                'summaryRows' => [
                    'Tipo de documento' => $this->document->document_type->label(),
                    'Número' => $this->document->getDocumentNumber(),
                    'Fecha de emisión' => optional($this->document->issue_date)->format('d/m/Y'),
                    'No. Autorización' => $this->document->authorization_number ?: $this->document->access_key,
                    'Total' => '$'.number_format((float) $this->document->total, 2),
                ],
                'accessKey' => $this->document->access_key,
                'ctaUrl' => route('portal.login'),
                'attachmentNames' => $this->attachmentNames(),
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = $this->attachments ?: $this->defaultAttachments();

        return collect($attachments)
            ->map(function (array $attachment) {
                if (Storage::disk('s3')->exists($attachment['path'])) {
                    return Attachment::fromStorageDisk('s3', $attachment['path'])
                        ->as($attachment['as'])
                        ->withMime($attachment['mime']);
                }

                if (Storage::exists($attachment['path'])) {
                    return Attachment::fromStorage($attachment['path'])
                        ->as($attachment['as'])
                        ->withMime($attachment['mime']);
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultAttachments(): array
    {
        $attachments = [];

        if ($this->document->ride_pdf_path) {
            $attachments[] = [
                'path' => $this->document->ride_pdf_path,
                'as' => $this->document->getDocumentNumber().'.pdf',
                'mime' => 'application/pdf',
            ];
        }

        $xmlPath = $this->document->xml_authorized_path ?: $this->document->xml_signed_path;
        if ($xmlPath) {
            $attachments[] = [
                'path' => $xmlPath,
                'as' => $this->document->getDocumentNumber().'.xml',
                'mime' => 'application/xml',
            ];
        }

        return $attachments;
    }

    /**
     * @return array<int, string>
     */
    private function attachmentNames(): array
    {
        return collect($this->defaultAttachments())
            ->pluck('as')
            ->values()
            ->all();
    }
}
