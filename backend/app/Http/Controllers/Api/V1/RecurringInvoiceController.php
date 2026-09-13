<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\DocumentCreationException;
use App\Http\Requests\Api\RecurringInvoiceRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\RecurringInvoiceResource;
use App\Models\Tenant\RecurringInvoice;
use App\Services\Document\DocumentTotals;
use App\Services\RecurringInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Facturas recurrentes
 */
class RecurringInvoiceController extends ApiController
{
    /**
     * Listar recurrentes
     *
     * @queryParam status string active | paused | completed | cancelled.
     * @queryParam customer_id int Filtra por cliente.
     * @queryParam search string Nombre de la recurrente o del cliente.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RecurringInvoice::where('tenant_id', $request->user()->tenant_id)
            ->with(['customer', 'company'])
            ->withCount('generatedDocuments');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        $items = $query->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('next_issue_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($items, RecurringInvoiceResource::class);
    }

    /**
     * Crear recurrente
     */
    public function store(RecurringInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $recurring = RecurringInvoice::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
            'status' => 'active',
            'items' => $this->templateItems($validated['items']),
            'next_issue_date' => $validated['next_issue_date'] ?? $validated['start_date'],
            'currency' => $validated['currency'] ?? 'DOLAR',
            'notify_before_issue' => $validated['notify_before_issue'] ?? true,
            'notify_days_before' => $validated['notify_days_before'] ?? 1,
            'auto_send' => $validated['auto_send'] ?? true,
        ]);

        return $this->created([
            'recurring_invoice' => new RecurringInvoiceResource($recurring->load(['customer', 'company', 'branch', 'emissionPoint'])),
        ], 'Recurrente creada');
    }

    /**
     * Ver recurrente (con sus últimas facturas generadas)
     */
    public function show(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);

        $recurringInvoice->load([
            'customer', 'company', 'branch', 'emissionPoint',
            'generatedDocuments' => fn ($q) => $q->with('customer')->orderByDesc('issue_date')->orderByDesc('id')->limit(10),
        ])->loadCount('generatedDocuments');

        return $this->success(['recurring_invoice' => new RecurringInvoiceResource($recurringInvoice)]);
    }

    /**
     * Editar recurrente
     */
    public function update(RecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);

        $validated = $request->validated();

        if (array_key_exists('items', $validated)) {
            $validated['items'] = $this->templateItems($validated['items']);
        }

        // Si cambia la próxima fecha, el recordatorio previo vuelve a aplicar.
        if (array_key_exists('next_issue_date', $validated)
            && $validated['next_issue_date']
            && $recurringInvoice->next_issue_date?->toDateString() !== $validated['next_issue_date']) {
            $validated['reminder_sent_for'] = null;
        }

        $recurringInvoice->update($validated);

        return $this->success([
            'recurring_invoice' => new RecurringInvoiceResource($recurringInvoice->fresh(['customer', 'company', 'branch', 'emissionPoint'])),
        ], 'Recurrente actualizada');
    }

    public function destroy(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);
        $recurringInvoice->delete();

        return $this->success(null, 'Recurrente eliminada');
    }

    public function pause(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);

        if ($recurringInvoice->status !== 'active') {
            return $this->error('Solo se pueden pausar recurrentes activas.', 400);
        }

        $recurringInvoice->update(['status' => 'paused']);

        return $this->success(['recurring_invoice' => new RecurringInvoiceResource($recurringInvoice->fresh(['customer']))], 'Recurrente pausada');
    }

    public function resume(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);

        if ($recurringInvoice->status !== 'paused') {
            return $this->error('Solo se pueden reanudar recurrentes pausadas.', 400);
        }

        if ($recurringInvoice->max_issues && $recurringInvoice->total_issued >= $recurringInvoice->max_issues) {
            return $this->error('La recurrente ya alcanzó su máximo de emisiones. Aumenta el máximo antes de reanudarla.', 400);
        }

        if ($recurringInvoice->end_date && now()->startOfDay()->greaterThan($recurringInvoice->end_date)) {
            return $this->error('La fecha de fin ya pasó. Ajusta la fecha de fin antes de reanudarla.', 400);
        }

        $recurringInvoice->update(['status' => 'active']);

        return $this->success(['recurring_invoice' => new RecurringInvoiceResource($recurringInvoice->fresh(['customer']))], 'Recurrente reanudada');
    }

    /**
     * Historial de facturas generadas por la recurrente
     */
    public function documents(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);

        $documents = $recurringInvoice->generatedDocuments()
            ->with(['customer'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($documents, DocumentResource::class);
    }

    /**
     * Emitir ahora
     *
     * Genera la factura de inmediato (sin esperar al lote diario) y avanza el
     * calendario. `send=false` la deja en borrador aunque la recurrente tenga
     * envío automático.
     */
    public function generate(Request $request, RecurringInvoice $recurringInvoice, RecurringInvoiceService $service): JsonResponse
    {
        $this->authorizeRecurring($request, $recurringInvoice);

        if (! $recurringInvoice->canIssue()) {
            return $this->error('La recurrente no está activa o ya alcanzó su fin/máximo de emisiones.', 400);
        }

        $send = $request->has('send') ? $request->boolean('send') : null;

        try {
            $document = $service->generateDocument($recurringInvoice, $send);
        } catch (DocumentCreationException $e) {
            $service->recordFailure($recurringInvoice, $e);

            return $this->error($e->getMessage(), $e->status, $e->errors);
        } catch (\Throwable $e) {
            $service->recordFailure($recurringInvoice, $e);

            return $this->error('No se pudo generar la factura: '.$e->getMessage(), 422);
        }

        return $this->created([
            'document' => new DocumentResource($document->load(['customer', 'company', 'items'])),
            'recurring_invoice' => new RecurringInvoiceResource($recurringInvoice->fresh(['customer', 'company', 'branch', 'emissionPoint'])),
        ], $document->status?->value === 'processing'
            ? 'Factura generada y enviada al SRI'
            : 'Factura generada como borrador');
    }

    /**
     * Plantilla de ítems guardada en la recurrente: solo lo que el usuario
     * define; los subtotales e IVA se calculan en cada emisión.
     *
     * @return array<int, array<string, mixed>>
     */
    private function templateItems(array $items): array
    {
        return collect($items)->values()->map(function (array $item) {
            [$code, $rate] = DocumentTotals::resolveTax($item);

            return [
                'product_id' => $item['product_id'] ?? null,
                'main_code' => $item['main_code'] ?? null,
                'aux_code' => $item['aux_code'] ?? null,
                'description' => $item['description'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'discount' => (float) ($item['discount'] ?? 0),
                'tax_rate' => $rate,
                'tax_percentage_code' => $code,
            ];
        })->all();
    }

    protected function authorizeRecurring(Request $request, RecurringInvoice $recurringInvoice): void
    {
        abort_if((int) $recurringInvoice->tenant_id !== (int) $request->user()->tenant_id, 403, 'No tienes acceso a esta recurrente.');
    }
}
