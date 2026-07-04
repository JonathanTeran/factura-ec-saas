<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Enums\JournalEntrySource;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Tenant\Company;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Contabilidad
 */
class OpeningBalanceController extends ApiController
{
    public function __construct(
        private readonly AccountingService $accountingService,
    ) {}

    /**
     * Registrar saldos iniciales
     *
     * Crea el asiento de apertura del ejercicio (caja, bancos, inventario,
     * deudas, capital…) para empresas que migran de otro sistema. Solo se
     * permite un asiento de apertura por año.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date' => ['nullable', 'date'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ]);

        $company = Company::find($request->user()->current_company_id)
            ?? $request->user()->tenant->companies()->first();

        if (! $company) {
            return $this->notFound('Aún no has configurado tu empresa.');
        }

        $entryDate = $data['entry_date'] ?? now()->startOfYear()->toDateString();
        $year = (int) date('Y', strtotime($entryDate));

        $existing = JournalEntry::where('company_id', $company->id)
            ->where('source_type', JournalEntrySource::OPENING)
            ->whereYear('entry_date', $year)
            ->exists();

        if ($existing) {
            return $this->validationError([
                'lines' => ["Ya existe un asiento de apertura para {$year}. Anúlalo antes de registrar otro."],
            ]);
        }

        // Resolver cuentas y validar cuadre
        $lines = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($data['lines'] as $i => $line) {
            $account = Account::where('company_id', $company->id)
                ->where('code', $line['account_code'])
                ->where('is_active', true)
                ->first();

            if (! $account) {
                return $this->validationError([
                    "lines.{$i}.account_code" => ["La cuenta {$line['account_code']} no existe en el plan de cuentas."],
                ]);
            }

            if (! $account->allows_movement) {
                return $this->validationError([
                    "lines.{$i}.account_code" => ["La cuenta {$account->code} ({$account->name}) es de agrupación y no acepta movimientos."],
                ]);
            }

            $debit = round((float) $line['debit'], 2);
            $credit = round((float) $line['credit'], 2);

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $lines[] = [
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
                'description' => 'Saldo inicial',
            ];
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return $this->validationError([
                'lines' => [sprintf(
                    'El asiento no cuadra: débitos $%.2f vs créditos $%.2f (diferencia $%.2f).',
                    $totalDebit,
                    $totalCredit,
                    abs($totalDebit - $totalCredit)
                )],
            ]);
        }

        if (count($lines) < 2) {
            return $this->validationError([
                'lines' => ['El asiento de apertura necesita al menos dos líneas con valores.'],
            ]);
        }

        $entry = $this->accountingService->forCompany($company)->createJournalEntry([
            'entry_date' => $entryDate,
            'description' => "Asiento de apertura {$year} (saldos iniciales)",
            'source_type' => JournalEntrySource::OPENING,
        ], $lines);

        $this->accountingService->postJournalEntry($entry);

        return $this->success([
            'entry' => $entry->fresh()->load('lines.account'),
        ], 'Saldos iniciales registrados correctamente.', 201);
    }
}
