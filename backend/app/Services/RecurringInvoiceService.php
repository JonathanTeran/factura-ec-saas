<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\DocumentCreationException;
use App\Exceptions\DocumentSendException;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\RecurringInvoice;
use App\Models\User;
use App\Notifications\RecurringInvoiceNotification;
use App\Services\Document\DocumentCreator;
use App\Services\Document\DocumentSender;
use App\Services\Document\DocumentTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Emisión de facturas recurrentes.
 *
 * Genera cada factura por el mismo camino que el panel (DocumentCreator:
 * secuencial atómico, clave de acceso, contadores del plan) y, si la
 * empresa está lista y la recurrente lo pide, la envía al SRI
 * (DocumentSender). Un error en una recurrente no tumba el lote: se guarda
 * en last_error y se avisa al dueño.
 */
class RecurringInvoiceService
{
    public function __construct(
        private readonly DocumentCreator $creator,
        private readonly DocumentSender $sender,
    ) {}

    /**
     * Procesa todas las recurrentes vencidas (next_issue_date <= hoy).
     *
     * @return array{processed: int, sent: int, failed: int, skipped: int}
     */
    public function processAllDue(): array
    {
        $results = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        $due = RecurringInvoice::withoutTenantScope()
            ->dueToday()
            ->with(['company', 'branch', 'emissionPoint', 'customer', 'tenant', 'createdBy'])
            ->orderBy('id')
            ->get();

        foreach ($due as $recurring) {
            try {
                if (! $recurring->canIssue()) {
                    // Activa pero fuera de rango (end_date pasado / máximo
                    // alcanzado): se cierra para que no vuelva a evaluarse.
                    $recurring->update(['status' => 'completed']);
                    $results['skipped']++;

                    continue;
                }

                $document = $this->generateDocument($recurring);
                $results['processed']++;
                if ($document->status === DocumentStatus::PROCESSING) {
                    $results['sent']++;
                }
            } catch (\Throwable $e) {
                $this->recordFailure($recurring, $e);
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Genera (y opcionalmente envía al SRI) la factura de una recurrente y
     * avanza su calendario. Lo usan el lote diario y "Emitir ahora".
     *
     * @throws DocumentCreationException|\Throwable
     */
    public function generateDocument(RecurringInvoice $recurring, ?bool $send = null): ElectronicDocument
    {
        $recurring->loadMissing(['tenant', 'company', 'branch', 'emissionPoint', 'customer', 'createdBy']);

        $tenant = $recurring->tenant;
        if (! $tenant) {
            throw new DocumentCreationException('La recurrente no tiene una cuenta asociada.', 422);
        }
        if (! $tenant->hasFeature('recurring_invoices')) {
            throw new DocumentCreationException('Tu plan no incluye facturación recurrente. Actualiza tu suscripción para continuar.', 403);
        }
        if (! $recurring->company || (int) $recurring->company->tenant_id !== (int) $tenant->id) {
            throw new DocumentCreationException('La empresa de la recurrente no existe o no pertenece a la cuenta.', 422);
        }

        $calc = DocumentTotals::fromItems($recurring->items ?? []);
        if ($calc['items'] === []) {
            throw new DocumentCreationException('La recurrente no tiene ítems: agrega al menos un producto o servicio.', 422);
        }

        // Fecha de emisión: la programada, salvo que se emita antes a mano
        // (el SRI no acepta fechas futuras).
        $scheduled = $recurring->next_issue_date ?? now();
        $issueDate = $scheduled->greaterThan(now()) ? now() : $scheduled;

        $total = $calc['totals']['total'];
        $paymentMethods = $this->normalizePaymentMethods($recurring->payment_methods, $total);
        $term = (int) ($paymentMethods[0]['term'] ?? 0);

        $data = [
            'company_id' => $recurring->company_id,
            'customer_id' => $recurring->customer_id,
            'emission_point_id' => $recurring->emission_point_id,
            'document_type' => DocumentType::FACTURA->value,
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $term > 0 ? $issueDate->copy()->addDays($term)->toDateString() : null,
            'currency' => $recurring->currency ?: 'DOLAR',
            'payment_methods' => $paymentMethods,
            'additional_info' => $recurring->additional_info ?? [],
            'notes' => $recurring->notes,
            'items' => $calc['items'],
            'recurring_invoice_id' => $recurring->id,
            ...$calc['totals'],
        ];

        $document = DB::transaction(function () use ($recurring, $tenant, $data) {
            $document = $this->creator->createDraft($tenant, $data, $this->actorFor($recurring));

            $recurring->advanceToNextIssue();
            $recurring->forceFill(['last_error' => null, 'last_error_at' => null])->save();

            return $document;
        });

        $shouldSend = $send ?? (bool) $recurring->auto_send;
        $sendError = null;

        if ($shouldSend) {
            try {
                $document = $this->sender->send($document);
            } catch (DocumentSendException $e) {
                $sendError = $e->getMessage();
                Log::warning("Recurrente #{$recurring->id}: factura #{$document->id} generada pero no enviada al SRI", [
                    'error' => $sendError,
                ]);
            }
        }

        Log::info("Recurrente #{$recurring->id}: factura #{$document->id} generada", [
            'sent' => $shouldSend && $sendError === null,
        ]);

        $this->notify(
            $recurring,
            $sendError ? RecurringInvoiceNotification::SEND_FAILED : RecurringInvoiceNotification::GENERATED,
            $document,
            $sendError
        );

        return $document;
    }

    /**
     * Recordatorio previo a la emisión (notify_days_before). Se envía una
     * sola vez por fecha programada (reminder_sent_for).
     */
    public function sendReminders(): int
    {
        $today = now()->startOfDay();
        $sent = 0;

        $candidates = RecurringInvoice::withoutTenantScope()
            ->active()
            ->where('notify_before_issue', true)
            ->where('notify_days_before', '>', 0)
            ->whereNotNull('next_issue_date')
            ->whereDate('next_issue_date', '>', $today->toDateString())
            ->whereDate('next_issue_date', '<=', $today->copy()->addDays(31)->toDateString())
            ->with(['customer', 'tenant', 'createdBy'])
            ->get();

        foreach ($candidates as $recurring) {
            $reminderDate = $recurring->reminderDate();
            if (! $reminderDate || $reminderDate->greaterThan($today)) {
                continue;
            }
            if ($recurring->reminder_sent_for && $recurring->reminder_sent_for->equalTo($recurring->next_issue_date)) {
                continue;
            }

            try {
                $this->notify($recurring, RecurringInvoiceNotification::UPCOMING);
                $recurring->forceFill(['reminder_sent_for' => $recurring->next_issue_date])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error("Recurrente #{$recurring->id}: no se pudo enviar el recordatorio", ['error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * Guarda el error en la recurrente y avisa al dueño (solo cuando el
     * mensaje cambia, para no repetir el mismo aviso cada día).
     */
    public function recordFailure(RecurringInvoice $recurring, \Throwable $e): void
    {
        $message = $e->getMessage() ?: get_class($e);

        Log::error("Recurrente #{$recurring->id}: falló la generación", [
            'error' => $message,
            'exception' => get_class($e),
        ]);

        $changed = $recurring->last_error !== $message;

        $recurring->forceFill([
            'last_error' => mb_substr($message, 0, 2000),
            'last_error_at' => now(),
        ])->save();

        if ($changed) {
            try {
                $this->notify($recurring, RecurringInvoiceNotification::FAILED, null, $message);
            } catch (\Throwable $notifyError) {
                Log::error("Recurrente #{$recurring->id}: no se pudo notificar el fallo", ['error' => $notifyError->getMessage()]);
            }
        }
    }

    /**
     * Formas de pago del comprobante. Acepta la forma completa del panel
     * ([{code, amount, term, time_unit}]) o una abreviada ([{method|code,
     * term|due_days}]); el monto siempre es el total calculado.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizePaymentMethods(?array $methods, float $total): array
    {
        $methods = array_values(array_filter($methods ?? [], 'is_array'));

        if ($methods === []) {
            return [['code' => '20', 'amount' => $total, 'term' => 0, 'time_unit' => 'dias']];
        }

        $normalized = [];
        foreach ($methods as $method) {
            $code = (string) ($method['code'] ?? $method['method'] ?? '20');
            $term = (int) ($method['term'] ?? $method['due_days'] ?? 0);
            $normalized[] = [
                'code' => $code !== '' ? $code : '20',
                'amount' => count($methods) === 1 ? $total : (float) ($method['amount'] ?? 0),
                'term' => $term,
                'time_unit' => (string) ($method['time_unit'] ?? 'dias'),
            ];
        }

        return $normalized;
    }

    /** Usuario que figura como creador de la factura generada. */
    private function actorFor(RecurringInvoice $recurring): ?User
    {
        $creator = $recurring->createdBy;
        if ($creator && $creator->is_active) {
            return $creator;
        }

        return $recurring->tenant?->owner ?? $creator;
    }

    private function notify(RecurringInvoice $recurring, string $event, ?ElectronicDocument $document = null, ?string $error = null): void
    {
        $recipients = collect([$recurring->tenant?->owner, $recurring->createdBy])
            ->filter(fn ($user) => $user && $user->is_active && filled($user->email))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new RecurringInvoiceNotification($event, $recurring, $document, $error));
    }
}
