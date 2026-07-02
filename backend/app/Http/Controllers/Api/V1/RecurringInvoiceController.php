<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\RecurringInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @tags Facturas recurrentes
 */
class RecurringInvoiceController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = RecurringInvoice::where('tenant_id', $request->user()->tenant_id)
            ->with(['customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->orderByDesc('next_issue_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')->where('tenant_id', $tenantId)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'emission_point_id' => ['required', Rule::exists('emission_points', 'id')->where('tenant_id', $tenantId)],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'frequency' => ['required', 'string', 'in:daily,weekly,biweekly,monthly,quarterly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'next_issue_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'payment_methods' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:10'],
            'max_issues' => ['nullable', 'integer', 'min:1'],
            'notify_before_issue' => ['nullable', 'boolean'],
            'notify_days_before' => ['nullable', 'integer', 'min:0'],
        ]);

        $recurring = RecurringInvoice::create([
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
            'status' => 'active',
            'currency' => $validated['currency'] ?? 'DOLAR',
            ...$validated,
        ]);

        return $this->created(['recurring_invoice' => $recurring->fresh(['customer'])], 'Recurrente creada');
    }

    public function show(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        abort_if($recurringInvoice->tenant_id !== $request->user()->tenant_id, 403);
        return $this->success(['recurring_invoice' => $recurringInvoice->load(['customer'])]);
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        abort_if($recurringInvoice->tenant_id !== $request->user()->tenant_id, 403);
        $validated = $request->validate([
            'frequency' => ['sometimes', 'string'],
            'next_issue_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:active,paused,cancelled,completed'],
            'items' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
        $recurringInvoice->update($validated);
        return $this->success(['recurring_invoice' => $recurringInvoice->fresh()], 'Actualizado');
    }

    public function destroy(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        abort_if($recurringInvoice->tenant_id !== $request->user()->tenant_id, 403);
        $recurringInvoice->delete();
        return $this->success(null, 'Eliminado');
    }

    public function pause(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        abort_if($recurringInvoice->tenant_id !== $request->user()->tenant_id, 403);
        $recurringInvoice->update(['status' => 'paused']);
        return $this->success(['recurring_invoice' => $recurringInvoice->fresh()], 'Pausada');
    }

    public function resume(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        abort_if($recurringInvoice->tenant_id !== $request->user()->tenant_id, 403);
        $recurringInvoice->update(['status' => 'active']);
        return $this->success(['recurring_invoice' => $recurringInvoice->fresh()], 'Reactivada');
    }
}
