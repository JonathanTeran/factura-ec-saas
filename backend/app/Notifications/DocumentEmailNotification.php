<?php

namespace App\Notifications;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class DocumentEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ElectronicDocument $document,
        public ?string $customMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company = $this->document->company;
        $documentTypeName = $this->document->document_type->label();

        $mail = (new MailMessage)
            ->subject("{$documentTypeName} #{$this->document->document_number} - {$company->business_name}")
            ->greeting("Estimado/a {$this->document->customer->business_name},")
            ->line("Adjunto encontrará su {$documentTypeName} electrónico/a.");

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        $mail->line("Número de documento: {$this->document->document_number}")
            ->line("Fecha de emisión: {$this->document->issue_date->format('d/m/Y')}")
            ->line("Clave de acceso: {$this->document->access_key}")
            ->line("Subtotal: \${$this->document->getSubtotal()}")
            ->line("IVA: \${$this->document->total_tax}")
            ->line("Total: \${$this->document->total}");

        // Attach PDF if exists
        if ($this->document->ride_pdf_path && Storage::exists($this->document->ride_pdf_path)) {
            $mail->attach(Storage::path($this->document->ride_pdf_path), [
                'as' => "Factura_{$this->document->document_number}.pdf",
                'mime' => 'application/pdf',
            ]);
        }

        // Attach XML if exists
        $xmlPath = $this->document->xml_authorized_path ?? $this->document->xml_signed_path;
        if ($xmlPath && Storage::exists($xmlPath)) {
            $mail->attach(Storage::path($xmlPath), [
                'as' => "Factura_{$this->document->document_number}.xml",
                'mime' => 'application/xml',
            ]);
        }

        $mail->line('Este documento fue generado electrónicamente y tiene plena validez tributaria.')
            ->salutation("Atentamente,\n{$company->business_name}");

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_email',
            'document_id' => $this->document->id,
            'document_number' => $this->document->document_number,
        ];
    }
}
