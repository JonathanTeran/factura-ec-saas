<?php

namespace App\Notifications;

use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\RecurringInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisos al dueño de la cuenta sobre sus facturas recurrentes:
 *  - generated:   se emitió la factura (con enlace al documento).
 *  - send_failed: se generó el borrador pero no se pudo enviar al SRI.
 *  - failed:      no se pudo generar (límite del plan, empresa incompleta...).
 *  - upcoming:    recordatorio previo según notify_days_before.
 */
class RecurringInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const GENERATED = 'generated';

    public const SEND_FAILED = 'send_failed';

    public const FAILED = 'failed';

    public const UPCOMING = 'upcoming';

    public function __construct(
        public string $event,
        public RecurringInvoice $recurring,
        public ?ElectronicDocument $document = null,
        public ?string $error = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting("Hola {$notifiable->name},")
            ->line($this->message());

        if ($this->error) {
            $mail->line("Detalle: {$this->error}");
        }

        if ($this->document) {
            $mail->line("Cliente: {$this->customerName()} · Total: $".number_format((float) $this->document->total, 2));
            $mail->action('Ver factura', url("/documents/{$this->document->id}"));
        } else {
            $mail->action('Ver recurrentes', url("/recurring-invoices/{$this->recurring->id}"));
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'recurring_invoice.'.$this->event,
            'recurring_invoice_id' => $this->recurring->id,
            'recurring_name' => $this->recurringName(),
            'document_id' => $this->document?->id,
            'document_number' => $this->document?->getDocumentNumber(),
            'customer_name' => $this->customerName(),
            'total' => $this->document ? (float) $this->document->total : null,
            'next_issue_date' => $this->recurring->next_issue_date?->toDateString(),
            'error' => $this->error,
            'message' => $this->message(),
            'url' => $this->document ? "/documents/{$this->document->id}" : "/recurring-invoices/{$this->recurring->id}",
        ];
    }

    private function subject(): string
    {
        return match ($this->event) {
            self::GENERATED => "Factura recurrente emitida · {$this->recurringName()}",
            self::SEND_FAILED => "Factura recurrente generada pero no enviada al SRI · {$this->recurringName()}",
            self::FAILED => "No se pudo generar la factura recurrente · {$this->recurringName()}",
            self::UPCOMING => "Próxima factura recurrente · {$this->recurringName()}",
            default => "Factura recurrente · {$this->recurringName()}",
        };
    }

    private function message(): string
    {
        $name = $this->recurringName();
        $number = $this->document?->getDocumentNumber();

        return match ($this->event) {
            self::GENERATED => $this->document && $this->document->status?->value === 'processing'
                ? "Se generó la factura {$number} de \"{$name}\" y se envió al SRI."
                : "Se generó la factura {$number} de \"{$name}\" como borrador. Revísala y envíala al SRI desde el panel.",
            self::SEND_FAILED => "Se generó la factura {$number} de \"{$name}\" pero no se pudo enviar al SRI. Corrige el problema y reenvíala desde el panel.",
            self::FAILED => "No se pudo generar la factura de \"{$name}\". Revisa la recurrente y vuelve a intentarlo.",
            self::UPCOMING => "La factura de \"{$name}\" se emitirá el ".($this->recurring->next_issue_date?->format('d/m/Y') ?? '—').'. Si necesitas cambiar algo, edítala o pásala antes.',
            default => "Novedad en la recurrente \"{$name}\".",
        };
    }

    private function recurringName(): string
    {
        return $this->recurring->name ?: ($this->customerName().' · '.$this->recurring->frequencyLabel());
    }

    private function customerName(): string
    {
        return $this->recurring->customer?->name ?? 'Cliente';
    }
}
