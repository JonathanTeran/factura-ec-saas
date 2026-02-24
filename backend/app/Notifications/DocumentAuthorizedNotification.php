<?php

namespace App\Notifications;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentAuthorizedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ElectronicDocument $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Documento Autorizado - {$this->document->document_number}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El documento {$this->document->document_type->label()} #{$this->document->document_number} ha sido autorizado por el SRI.")
            ->line("Clave de acceso: {$this->document->access_key}")
            ->line("Cliente: {$this->document->customer->business_name}")
            ->line("Total: \${$this->document->total}")
            ->action('Ver Documento', url("/panel/documents/{$this->document->id}"))
            ->line('Puedes descargar el PDF y XML desde el panel.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_authorized',
            'document_id' => $this->document->id,
            'document_number' => $this->document->document_number,
            'document_type' => $this->document->document_type,
            'access_key' => $this->document->access_key,
            'customer_name' => $this->document->customer->business_name,
            'total' => $this->document->total,
            'message' => "Documento {$this->document->document_number} autorizado",
        ];
    }
}
