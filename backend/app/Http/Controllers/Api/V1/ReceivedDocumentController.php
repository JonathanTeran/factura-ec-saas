<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\ReceivedDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @tags Documentos recibidos
 */
class ReceivedDocumentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = ReceivedDocument::where('tenant_id', $request->user()->tenant_id);

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('issuer_name', 'like', "%{$term}%")
                    ->orWhere('issuer_ruc', 'like', "%{$term}%")
                    ->orWhere('access_key', 'like', "%{$term}%");
            });
        }

        if ($request->filled('expense_category')) {
            $query->where('expense_category', $request->input('expense_category'));
        }

        if ($request->filled('from')) {
            $query->whereDate('issue_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('issue_date', '<=', $request->input('to'));
        }

        $docs = $query->orderByDesc('issue_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $docs->items(),
            'meta' => [
                'current_page' => $docs->currentPage(),
                'last_page' => $docs->lastPage(),
                'per_page' => $docs->perPage(),
                'total' => $docs->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'document_type' => ['required', 'string', 'max:2'],
            'access_key' => ['nullable', 'string', 'max:49'],
            'authorization_number' => ['nullable', 'string', 'max:49'],
            'authorization_date' => ['nullable', 'date'],
            'issuer_ruc' => ['required', 'string', 'max:13'],
            'issuer_name' => ['required', 'string', 'max:300'],
            'issue_date' => ['required', 'date'],
            'subtotal_0' => ['nullable', 'numeric', 'min:0'],
            'subtotal_5' => ['nullable', 'numeric', 'min:0'],
            'subtotal_12' => ['nullable', 'numeric', 'min:0'],
            'subtotal_15' => ['nullable', 'numeric', 'min:0'],
            'subtotal_no_tax' => ['nullable', 'numeric', 'min:0'],
            'total_discount' => ['nullable', 'numeric', 'min:0'],
            'total_tax' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'expense_category' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $doc = ReceivedDocument::create([
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
            'is_processed' => false,
            ...$validated,
        ]);

        return $this->created(['received_document' => $doc], 'Documento recibido registrado');
    }

    public function show(Request $request, ReceivedDocument $receivedDocument): JsonResponse
    {
        abort_if($receivedDocument->tenant_id !== $request->user()->tenant_id, 403);
        return $this->success(['received_document' => $receivedDocument]);
    }

    public function update(Request $request, ReceivedDocument $receivedDocument): JsonResponse
    {
        abort_if($receivedDocument->tenant_id !== $request->user()->tenant_id, 403);
        $validated = $request->validate([
            'expense_category' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_processed' => ['nullable', 'boolean'],
        ]);
        $receivedDocument->update($validated);
        return $this->success(['received_document' => $receivedDocument->fresh()], 'Actualizado');
    }

    public function destroy(Request $request, ReceivedDocument $receivedDocument): JsonResponse
    {
        abort_if($receivedDocument->tenant_id !== $request->user()->tenant_id, 403);
        $receivedDocument->delete();
        return $this->success(null, 'Eliminado');
    }
}
