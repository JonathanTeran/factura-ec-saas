<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\DocumentCreationException;
use App\Http\Requests\Api\ConvertQuoteRequest;
use App\Http\Requests\Api\QuoteRequest;
use App\Http\Requests\Api\SendQuoteRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\QuoteResource;
use App\Models\Tenant\Quote;
use App\Services\Quote\QuotePdfGenerator;
use App\Services\Quote\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * @tags Cotizaciones
 */
class QuoteController extends ApiController
{
    public function __construct(
        private readonly QuoteService $quotes,
        private readonly QuotePdfGenerator $pdf,
    ) {}

    /**
     * Listar cotizaciones
     *
     * @queryParam status string draft | sent | accepted | rejected | invoiced | expired.
     * @queryParam customer_id int Filtra por cliente.
     * @queryParam search string Número o nombre del cliente.
     * @queryParam date_from string Fecha de emisión desde (YYYY-MM-DD).
     * @queryParam date_to string Fecha de emisión hasta (YYYY-MM-DD).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Quote::where('tenant_id', $request->user()->tenant_id)
            ->with(['customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('quote_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        $quotes = $query->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($quotes, QuoteResource::class);
    }

    /**
     * Crear cotización
     *
     * Los totales se calculan en el servidor a partir de los ítems.
     */
    public function store(QuoteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $quote = $this->quotes->create([
            'tenant_id' => $request->user()->tenant_id,
            'company_id' => $validated['company_id'],
            'customer_id' => $validated['customer_id'],
            'created_by' => $request->user()->id,
            'quote_number' => $validated['quote_number'] ?? null,
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
        ], $validated['items']);

        return $this->created([
            'quote' => new QuoteResource($quote->load(['customer', 'items'])),
        ], 'Cotización creada exitosamente');
    }

    public function show(Request $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        return $this->success([
            'quote' => new QuoteResource($quote->load(['customer', 'company', 'items', 'convertedDocument'])),
        ]);
    }

    /**
     * Editar cotización (solo en borrador o enviada)
     */
    public function update(QuoteRequest $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        if (! $quote->canBeEdited()) {
            return $this->error('Solo se pueden editar cotizaciones en borrador o enviadas.', 400);
        }

        $validated = $request->validated();
        $data = collect($validated)->except('items')->toArray();

        $this->quotes->update($quote, $data, $validated['items'] ?? null);

        return $this->success([
            'quote' => new QuoteResource($quote->fresh(['customer', 'items'])),
        ], 'Cotización actualizada');
    }

    public function destroy(Request $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        if (! $quote->canBeDeleted()) {
            return $this->error('Esta cotización ya fue convertida en factura y no se puede eliminar.', 400);
        }

        $quote->items()->delete();
        $quote->delete();

        return $this->success(null, 'Cotización eliminada');
    }

    /**
     * Enviar por correo
     *
     * Envía la cotización en PDF al cliente (o al correo indicado) y la marca
     * como enviada.
     */
    public function send(SendQuoteRequest $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        try {
            $quote = $this->quotes->send($quote, $request->input('email'), $request->input('message'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(['quote' => new QuoteResource($quote)], "Cotización enviada a {$quote->sent_to}");
    }

    public function accept(Request $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        try {
            $quote = $this->quotes->accept($quote);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(['quote' => new QuoteResource($quote)], 'Cotización aceptada');
    }

    public function reject(Request $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        try {
            $quote = $this->quotes->reject($quote);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(['quote' => new QuoteResource($quote)], 'Cotización rechazada');
    }

    /**
     * Convertir en factura
     *
     * Crea la factura electrónica con los ítems de la cotización (mismo flujo
     * que "Nueva factura") y marca la cotización como facturada. Con
     * `send=true` la envía al SRI en el mismo paso.
     */
    public function convert(ConvertQuoteRequest $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        try {
            $result = $this->quotes->convert($quote, $request->user(), $request->validated());
        } catch (DocumentCreationException $e) {
            return $this->error($e->getMessage(), $e->status, $e->errors);
        }

        $document = $result['document']->load(['customer', 'company', 'items']);

        $message = match (true) {
            $result['sent'] => 'Cotización convertida y factura enviada al SRI.',
            $result['send_error'] !== null => 'Cotización convertida en factura (borrador), pero no se pudo enviar al SRI: '.$result['send_error'],
            default => 'Cotización convertida en factura (borrador).',
        };

        return $this->created([
            'quote' => new QuoteResource($quote->fresh(['customer', 'items', 'convertedDocument'])),
            'document' => new DocumentResource($document),
            'sent' => $result['sent'],
            'send_error' => $result['send_error'],
        ], $message);
    }

    /**
     * PDF de la cotización
     *
     * Devuelve una URL temporal (30 min) para abrir/descargar el PDF.
     */
    public function pdf(Request $request, Quote $quote): JsonResponse
    {
        $this->authorizeQuote($request, $quote);

        return $this->success([
            'url' => $this->publicPdfUrl($quote->id),
            'filename' => $this->pdf->filename($quote),
        ]);
    }

    /**
     * PDF en línea (URL temporal firmada, sin sesión)
     */
    public function streamPdfPublic(Request $request, string $quote)
    {
        $this->verifyPdfToken((int) $quote, $request);

        $model = Quote::withoutGlobalScopes()->findOrFail($quote);

        return response($this->pdf->render($model), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->pdf->filename($model).'"',
        ]);
    }

    protected function authorizeQuote(Request $request, Quote $quote): void
    {
        abort_if((int) $quote->tenant_id !== (int) $request->user()->tenant_id, 403, 'No tienes acceso a esta cotización.');
    }

    protected function publicPdfUrl(int $id): string
    {
        $expires = now()->addMinutes(30)->timestamp;

        return rtrim(config('app.url'), '/')
            ."/api/v1/public/quotes/{$id}/pdf?e={$expires}&t=".$this->pdfToken($id, $expires);
    }

    protected function pdfToken(int $id, int $expires): string
    {
        return hash_hmac('sha256', "quote-pdf:{$id}:{$expires}", (string) config('app.key'));
    }

    protected function verifyPdfToken(int $id, Request $request): void
    {
        $expires = (int) $request->query('e');
        $token = (string) $request->query('t');

        if ($expires < now()->timestamp) {
            abort(403, 'El enlace expiró. Generá uno nuevo.');
        }
        if (! hash_equals($this->pdfToken($id, $expires), $token)) {
            abort(403, 'Enlace inválido.');
        }
    }
}
