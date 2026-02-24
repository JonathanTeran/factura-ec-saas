<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\JournalEntry;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends ApiController
{
    public function __construct(
        private readonly AccountingService $accountingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::where('company_id', $request->user()->current_company_id ?? 0)
            ->with(['createdByUser', 'fiscalPeriod'])
            ->orderByDesc('entry_date');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('entry_date', [$request->input('from'), $request->input('to')]);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $entries->items(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $request->user()->currentCompany;

        try {
            $entry = $this->accountingService
                ->forCompany($company)
                ->createJournalEntry(
                    collect($validated)->except('lines')->toArray(),
                    $validated['lines']
                );

            return $this->created(['journal_entry' => $entry], 'Asiento contable creado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show(Request $request, JournalEntry $entry): JsonResponse
    {
        $this->authorizeEntry($request, $entry);

        return $this->success([
            'journal_entry' => $entry->load(['lines.account', 'lines.costCenter', 'createdByUser', 'postedByUser', 'voidedByUser', 'fiscalPeriod']),
        ]);
    }

    public function update(Request $request, JournalEntry $entry): JsonResponse
    {
        $this->authorizeEntry($request, $entry);

        $validated = $request->validate([
            'entry_date' => ['sometimes', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $request->user()->currentCompany;

        try {
            $entry = $this->accountingService
                ->forCompany($company)
                ->updateJournalEntry(
                    $entry,
                    collect($validated)->except('lines')->toArray(),
                    $validated['lines']
                );

            return $this->success(['journal_entry' => $entry], 'Asiento actualizado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy(Request $request, JournalEntry $entry): JsonResponse
    {
        $this->authorizeEntry($request, $entry);

        if ($entry->status->value !== 'draft') {
            return $this->error('Solo se pueden eliminar asientos en borrador.');
        }

        $entry->delete();

        return $this->success(null, 'Asiento eliminado exitosamente');
    }

    public function postEntry(Request $request, JournalEntry $entry): JsonResponse
    {
        $this->authorizeEntry($request, $entry);
        $company = $request->user()->currentCompany;

        try {
            $entry = $this->accountingService
                ->forCompany($company)
                ->postJournalEntry($entry);

            return $this->success(['journal_entry' => $entry], 'Asiento contabilizado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    public function voidEntry(Request $request, JournalEntry $entry): JsonResponse
    {
        $this->authorizeEntry($request, $entry);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $company = $request->user()->currentCompany;

        try {
            $entry = $this->accountingService
                ->forCompany($company)
                ->voidJournalEntry($entry, $validated['reason']);

            return $this->success(['journal_entry' => $entry], 'Asiento anulado exitosamente');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function authorizeEntry(Request $request, JournalEntry $entry): void
    {
        if ($entry->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este asiento.');
        }
    }
}
