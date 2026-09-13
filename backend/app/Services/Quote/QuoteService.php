<?php

namespace App\Services\Quote;

use App\Enums\DocumentType;
use App\Enums\QuoteStatus;
use App\Exceptions\DocumentCreationException;
use App\Exceptions\DocumentSendException;
use App\Mail\QuoteMail;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Quote;
use App\Models\Tenant\QuoteItem;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Document\DocumentCreator;
use App\Services\Document\DocumentSender;
use App\Services\Document\DocumentTotals;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Proformas: numeración única por tenant, totales calculados en el
 * servidor, envío por correo con PDF, aceptación/rechazo, conversión en
 * factura electrónica (DocumentCreator/DocumentSender) y vencimiento.
 */
class QuoteService
{
    public const NUMBER_PREFIX = 'COT-';

    public function __construct(
        private readonly DocumentCreator $creator,
        private readonly DocumentSender $sender,
    ) {}

    /**
     * Siguiente número COT-000001 del tenant. Debe llamarse dentro de una
     * transacción: bloquea la fila del tenant para serializar la numeración
     * entre peticiones concurrentes (el índice único es la última defensa).
     */
    public function generateQuoteNumber(int $tenantId): string
    {
        Tenant::query()->whereKey($tenantId)->lockForUpdate()->first();

        $cast = DB::connection()->getDriverName() === 'mysql' ? 'UNSIGNED' : 'INTEGER';
        $offset = strlen(self::NUMBER_PREFIX) + 1;

        $max = (int) Quote::withoutTenantScope()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('quote_number', 'like', self::NUMBER_PREFIX.'%')
            ->selectRaw("MAX(CAST(SUBSTR(quote_number, {$offset}) AS {$cast})) as max_number")
            ->value('max_number');

        return self::NUMBER_PREFIX.str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data  tenant_id, company_id, customer_id,
     *                                      created_by, issue_date, expiry_date, notes, payment_terms,
     *                                      quote_number (opcional).
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $data, array $items): Quote
    {
        $attempts = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($data, $items) {
                    $data['quote_number'] = filled($data['quote_number'] ?? null)
                        ? $data['quote_number']
                        : $this->generateQuoteNumber((int) $data['tenant_id']);
                    $data['status'] = $data['status'] ?? QuoteStatus::DRAFT->value;

                    $quote = Quote::create($data);
                    $this->syncItems($quote, $items);

                    return $quote->fresh(['items']);
                });
            } catch (QueryException $e) {
                // Choque del índice único (dos peticiones simultáneas): se
                // recalcula el número y se reintenta.
                if (++$attempts >= 3 || ! $this->isDuplicateKey($e) || filled($data['quote_number'] ?? null)) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>|null  $items  null = no tocar los ítems
     */
    public function update(Quote $quote, array $data, ?array $items = null): Quote
    {
        return DB::transaction(function () use ($quote, $data, $items) {
            $quote->update($data);

            if ($items !== null) {
                $this->syncItems($quote, $items);
            } else {
                $this->recalculateTotals($quote);
            }

            return $quote->fresh(['items']);
        });
    }

    /**
     * Reemplaza las líneas recalculando subtotal, IVA y total de cada una y
     * los totales de la proforma (nunca se confía en los valores del cliente).
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncItems(Quote $quote, array $items): void
    {
        $quote->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $line = DocumentTotals::normalizeItem([
                ...$item,
                // Los totales de línea siempre se recalculan aquí.
                'subtotal' => null,
                'tax_base' => null,
                'tax_value' => null,
            ], $index);

            QuoteItem::create([
                'quote_id' => $quote->id,
                'product_id' => $line['product_id'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount' => $line['discount'],
                'tax_rate' => $line['tax_rate'],
                'subtotal' => $line['subtotal'],
                'tax_value' => $line['tax_value'],
                'total' => DocumentTotals::round($line['subtotal'] + $line['tax_value']),
                'sort_order' => $index,
            ]);
        }

        $this->recalculateTotals($quote);
    }

    public function recalculateTotals(Quote $quote): void
    {
        $quote->load('items');

        $quote->update([
            'subtotal' => DocumentTotals::round((float) $quote->items->sum('subtotal')),
            'total_discount' => DocumentTotals::round((float) $quote->items->sum('discount')),
            'total_tax' => DocumentTotals::round((float) $quote->items->sum('tax_value')),
            'total' => DocumentTotals::round((float) $quote->items->sum('total')),
        ]);
    }

    /**
     * Envía la proforma por correo (PDF adjunto) y la marca como enviada.
     *
     * @throws InvalidArgumentException sin destinatario o estado no enviable
     */
    public function send(Quote $quote, ?string $email = null, ?string $message = null): Quote
    {
        if (! $quote->canBeSent()) {
            throw new InvalidArgumentException('Esta cotización ya fue '.mb_strtolower($quote->status->label()).' y no se puede enviar.');
        }

        $quote->loadMissing(['customer', 'company', 'items']);
        $recipient = $email ?: $quote->customer?->email;

        if (! $recipient) {
            throw new InvalidArgumentException('El cliente no tiene correo registrado. Indica un correo para enviar la cotización.');
        }

        Mail::to($recipient)->queue(new QuoteMail($quote, $message));

        $quote->update([
            'status' => $quote->status === QuoteStatus::DRAFT ? QuoteStatus::SENT : $quote->status,
            'sent_at' => now(),
            'sent_to' => $recipient,
        ]);

        return $quote->fresh(['customer', 'items']);
    }

    public function accept(Quote $quote): Quote
    {
        if (! $quote->canBeAccepted()) {
            throw new InvalidArgumentException('Solo se pueden aceptar cotizaciones en borrador o enviadas.');
        }

        $quote->update(['status' => QuoteStatus::ACCEPTED, 'accepted_at' => now()]);

        return $quote->fresh(['customer', 'items']);
    }

    public function reject(Quote $quote): Quote
    {
        if (! $quote->canBeRejected()) {
            throw new InvalidArgumentException('Esta cotización ya fue '.mb_strtolower($quote->status->label()).' y no se puede rechazar.');
        }

        $quote->update(['status' => QuoteStatus::REJECTED, 'rejected_at' => now()]);

        return $quote->fresh(['customer', 'items']);
    }

    /**
     * Convierte la proforma en una factura electrónica (borrador) y, si se
     * pide, la envía al SRI en el mismo paso.
     *
     * @param  array<string, mixed>  $options  emission_point_id (requerido),
     *                                         issue_date, payment_method, payment_term, payment_methods, send.
     * @return array{document: ElectronicDocument, sent: bool, send_error: ?string}
     *
     * @throws DocumentCreationException
     */
    public function convert(Quote $quote, User $user, array $options): array
    {
        if (! $quote->canBeConverted()) {
            throw new DocumentCreationException(
                $quote->converted_to_document_id
                    ? 'Esta cotización ya fue convertida en factura.'
                    : 'Solo se pueden convertir cotizaciones en borrador, enviadas o aceptadas.',
                400
            );
        }

        $quote->loadMissing(['items.product', 'company', 'customer', 'tenant']);

        $items = $quote->items->values()->map(function (QuoteItem $item, int $index) {
            $product = $item->product;
            $sameRate = $product && (float) $product->tax_rate === (float) $item->tax_rate;

            return [
                'product_id' => $item->product_id,
                'main_code' => $product?->main_code ?: 'ITEM-'.($index + 1),
                'aux_code' => $product?->aux_code,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax_rate' => (float) $item->tax_rate,
                // El código SRI del producto manda si su tarifa coincide; si
                // el usuario cambió la tarifa en la proforma se deriva de ella.
                'tax_percentage_code' => $sameRate ? $product->tax_percentage_code : null,
            ];
        })->all();

        $calc = DocumentTotals::fromItems($items);
        $total = $calc['totals']['total'];

        $paymentMethods = $options['payment_methods'] ?? null;
        if (! $paymentMethods) {
            $paymentMethods = [[
                'code' => (string) ($options['payment_method'] ?? '20'),
                'amount' => $total,
                'term' => (int) ($options['payment_term'] ?? 0),
                'time_unit' => 'dias',
            ]];
        }

        $additionalInfo = ['Cotización' => $quote->quote_number];
        if (filled($quote->payment_terms)) {
            $additionalInfo['Condiciones de pago'] = mb_substr($quote->payment_terms, 0, 300);
        }

        $data = [
            'company_id' => $quote->company_id,
            'customer_id' => $quote->customer_id,
            'emission_point_id' => $options['emission_point_id'] ?? null,
            'document_type' => DocumentType::FACTURA->value,
            'issue_date' => $options['issue_date'] ?? now()->toDateString(),
            'payment_methods' => $paymentMethods,
            'additional_info' => $additionalInfo,
            'notes' => $quote->notes,
            'items' => $calc['items'],
            ...$calc['totals'],
        ];

        $document = DB::transaction(function () use ($quote, $user, $data) {
            $document = $this->creator->createDraft($quote->tenant, $data, $user);

            $quote->update([
                'status' => QuoteStatus::INVOICED,
                'converted_to_document_id' => $document->id,
                'converted_at' => now(),
            ]);

            return $document;
        });

        $sent = false;
        $sendError = null;

        if (! empty($options['send'])) {
            try {
                $document = $this->sender->send($document);
                $sent = true;
            } catch (DocumentSendException $e) {
                $sendError = $e->getMessage();
            }
        }

        return ['document' => $document, 'sent' => $sent, 'send_error' => $sendError];
    }

    /** Marca como vencidas las proformas abiertas cuya fecha de validez pasó. */
    public function markExpired(): int
    {
        return Quote::withoutTenantScope()
            ->whereIn('status', [QuoteStatus::DRAFT->value, QuoteStatus::SENT->value])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->update(['status' => QuoteStatus::EXPIRED->value]);
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000'
            || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
