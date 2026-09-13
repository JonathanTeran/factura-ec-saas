<?php

namespace App\Mail;

use App\Models\Tenant\Quote;
use App\Services\Quote\QuotePdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo al cliente con la proforma en PDF. Se encola en 'emails'; el PDF se
 * genera en el worker al momento de enviar.
 */
class QuoteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public ?string $customMessage = null,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $company = $this->quote->company;
        $issuer = $company?->trade_name ?: $company?->business_name;

        return new Envelope(
            subject: "Cotización {$this->quote->quote_number}".($issuer ? " · {$issuer}" : ''),
            replyTo: filled($company?->email) ? [$company->email] : [],
        );
    }

    public function content(): Content
    {
        $this->quote->loadMissing(['company', 'customer', 'items']);

        return new Content(
            view: 'emails.quote',
            with: [
                'quote' => $this->quote,
                'company' => $this->quote->company,
                'customer' => $this->quote->customer,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        $generator = app(QuotePdfGenerator::class);

        return [
            Attachment::fromData(fn () => $generator->render($this->quote), $generator->filename($this->quote))
                ->withMime('application/pdf'),
        ];
    }
}
