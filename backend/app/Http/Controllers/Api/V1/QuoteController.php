<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\QuoteResource;
use App\Models\Tenant\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @tags Cotizaciones
 */
class QuoteController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Quote::where('tenant_id', $request->user()->tenant_id)
            ->with(['customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('quote_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        $quotes = $query->orderByDesc('issue_date')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($quotes, QuoteResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')->where('tenant_id', $tenantId)],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'quote_number' => ['nullable', 'string', 'max:30'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'total_discount' => ['nullable', 'numeric', 'min:0'],
            'total_tax' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.tax_value' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        $userId = $request->user()->id;

        $quote = DB::transaction(function () use ($validated, $tenantId, $userId) {
            $number = $validated['quote_number'] ?? 'COT-'.str_pad(
                (string) (Quote::where('tenant_id', $tenantId)->count() + 1),
                6,
                '0',
                STR_PAD_LEFT,
            );

            $quote = Quote::create([
                'tenant_id' => $tenantId,
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'created_by' => $userId,
                'quote_number' => $number,
                'status' => 'draft',
                'issue_date' => $validated['issue_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'subtotal' => $validated['subtotal'],
                'total_discount' => $validated['total_discount'] ?? 0,
                'total_tax' => $validated['total_tax'],
                'total' => $validated['total'],
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $quote->items()->create($item);
            }

            return $quote;
        });

        return $this->created([
            'quote' => new QuoteResource($quote->load(['customer', 'items'])),
        ], 'Cotización creada exitosamente');
    }

    public function show(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->tenant_id !== $request->user()->tenant_id, 403);

        return $this->success([
            'quote' => new QuoteResource($quote->load(['customer', 'items'])),
        ]);
    }

    public function update(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->tenant_id !== $request->user()->tenant_id, 403);

        if ($quote->status?->value !== 'draft' && $quote->status !== 'draft') {
            return $this->error('Solo se pueden editar cotizaciones en borrador.', 400);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', Rule::exists('customers', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'issue_date' => ['sometimes', 'date'],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'total_discount' => ['sometimes', 'numeric', 'min:0'],
            'total_tax' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'payment_terms' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'array'],
        ]);

        DB::transaction(function () use ($quote, $validated) {
            $quote->update(collect($validated)->except('items')->toArray());

            if (isset($validated['items'])) {
                $quote->items()->delete();
                foreach ($validated['items'] as $item) {
                    $quote->items()->create($item);
                }
            }
        });

        return $this->success([
            'quote' => new QuoteResource($quote->fresh(['customer', 'items'])),
        ], 'Cotización actualizada');
    }

    public function destroy(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->tenant_id !== $request->user()->tenant_id, 403);

        if ($quote->converted_to_document_id) {
            return $this->error('Esta cotización ya fue convertida en factura y no se puede eliminar.', 400);
        }

        $quote->items()->delete();
        $quote->delete();

        return $this->success(null, 'Cotización eliminada');
    }

    public function send(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->tenant_id !== $request->user()->tenant_id, 403);
        $quote->update(['status' => 'sent']);
        return $this->success(['quote' => new QuoteResource($quote->fresh())], 'Cotización marcada como enviada');
    }

    public function accept(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->tenant_id !== $request->user()->tenant_id, 403);
        $quote->update(['status' => 'accepted']);
        return $this->success(['quote' => new QuoteResource($quote->fresh())], 'Cotización aceptada');
    }

    public function reject(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->tenant_id !== $request->user()->tenant_id, 403);
        $quote->update(['status' => 'rejected']);
        return $this->success(['quote' => new QuoteResource($quote->fresh())], 'Cotización rechazada');
    }
}
