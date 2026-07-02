<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Accounting\AccountingSetting;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\DocumentPayment;
use App\Services\Accounting\AutoJournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @tags Cobros
 */
class DocumentPaymentController extends ApiController
{
    /**
     * Listar cobros de un comprobante
     */
    public function index(Request $request, ElectronicDocument $document): JsonResponse
    {
        return $this->success([
            'payments' => $document->payments()->orderByDesc('payment_date')->get(),
            'document' => $this->documentTotals($document),
        ]);
    }

    /**
     * Registrar un cobro
     *
     * Registra el cobro (total o parcial) de un comprobante y genera el
     * asiento contable automático (Caja/Bancos contra Cuentas por cobrar)
     * si la contabilidad está activa.
     */
    public function store(Request $request, ElectronicDocument $document): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,transfer,card,other'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $balance = $document->balance;
        if ((float) $data['amount'] > $balance + 0.001) {
            return $this->validationError([
                'amount' => ["El cobro (\${$data['amount']}) supera el saldo pendiente (\${$balance})."],
            ]);
        }

        $payment = DocumentPayment::create([
            'tenant_id' => $document->tenant_id,
            'electronic_document_id' => $document->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $this->generateAccountingEntry($payment, $document);

        return $this->success([
            'payment' => $payment,
            'document' => $this->documentTotals($document->fresh()),
        ], 'Cobro registrado correctamente.', 201);
    }

    private function generateAccountingEntry(DocumentPayment $payment, ElectronicDocument $document): void
    {
        $company = $document->company;
        $tenant = $document->tenant;

        if (! $company || ! $tenant?->has_accounting) {
            return;
        }

        $settings = AccountingSetting::where('company_id', $company->id)->first();
        if (! $settings || ! $settings->auto_journal_entries) {
            return;
        }

        try {
            app(AutoJournalEntryService::class)
                ->forCompany($company)
                ->generateFromPayment($payment);
        } catch (\Throwable $e) {
            Log::error("Error generando asiento para cobro {$payment->id}: {$e->getMessage()}");
        }
    }

    private function documentTotals(ElectronicDocument $document): array
    {
        return [
            'id' => $document->id,
            'total' => number_format((float) $document->total, 2, '.', ''),
            'paid_amount' => number_format($document->paid_amount, 2, '.', ''),
            'balance' => number_format($document->balance, 2, '.', ''),
        ];
    }
}
