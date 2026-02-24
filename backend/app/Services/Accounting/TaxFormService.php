<?php

namespace App\Services\Accounting;

use App\Enums\TaxFormType;
use App\Models\Accounting\Account;
use App\Models\Accounting\TaxFormSubmission;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\Purchase;
use Illuminate\Support\Facades\DB;

class TaxFormService
{
    private Company $company;

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Formulario 103 - Retenciones en la Fuente del IR
     */
    public function generateF103(int $year, int $month): array
    {
        $from = "{$year}-{$month}-01";
        $to = date('Y-m-t', strtotime($from));

        // Retenciones emitidas en el período
        $retentions = ElectronicDocument::where('company_id', $this->company->id)
            ->where('document_type', '07')
            ->where('status', 'authorized')
            ->whereBetween('issue_date', [$from, $to])
            ->with('withholdingDetails')
            ->get();

        $casilleros = [];

        foreach ($retentions as $retention) {
            foreach ($retention->withholdingDetails ?? [] as $detail) {
                if (($detail['tax_type'] ?? '') !== 'renta') {
                    continue;
                }

                $code = $detail['retention_code'] ?? '';
                $baseKey = "base_{$code}";
                $retKey = "ret_{$code}";

                $casilleros[$baseKey] = ($casilleros[$baseKey] ?? 0) + (float) ($detail['tax_base'] ?? 0);
                $casilleros[$retKey] = ($casilleros[$retKey] ?? 0) + (float) ($detail['retained_value'] ?? 0);
            }
        }

        return [
            'form_type' => TaxFormType::F103->value,
            'year' => $year,
            'month' => $month,
            'company' => [
                'ruc' => $this->company->ruc,
                'business_name' => $this->company->business_name,
            ],
            'casilleros' => $casilleros,
            'total_retenciones' => array_sum(array_filter($casilleros, fn ($k) => str_starts_with($k, 'ret_'), ARRAY_FILTER_USE_KEY)),
        ];
    }

    /**
     * Formulario 104 - IVA
     */
    public function generateF104(int $year, int $month): array
    {
        $from = "{$year}-{$month}-01";
        $to = date('Y-m-t', strtotime($from));

        // Ventas del período
        $ventas = ElectronicDocument::where('company_id', $this->company->id)
            ->whereIn('document_type', ['01', '06']) // Facturas y Liquidaciones
            ->where('status', 'authorized')
            ->whereBetween('issue_date', [$from, $to])
            ->get();

        // Notas de crédito
        $notasCredito = ElectronicDocument::where('company_id', $this->company->id)
            ->where('document_type', '04')
            ->where('status', 'authorized')
            ->whereBetween('issue_date', [$from, $to])
            ->get();

        // Compras del período
        $compras = Purchase::where('company_id', $this->company->id)
            ->where('status', '!=', 'voided')
            ->whereBetween('issue_date', [$from, $to])
            ->get();

        $ventasSubtotal0 = $ventas->sum('subtotal_0');
        $ventasSubtotal12 = $ventas->sum('subtotal_12');
        $ventasSubtotal15 = $ventas->sum('subtotal_15');
        $ventasIVA = $ventas->sum('total_tax');

        $ncSubtotal = $notasCredito->sum('subtotal');
        $ncIVA = $notasCredito->sum('total_tax');

        $comprasSubtotal0 = $compras->sum('subtotal_0');
        $comprasSubtotalGravado = $compras->sum('subtotal_12') + $compras->sum('subtotal_15');
        $comprasIVA = $compras->sum('total_tax');

        $ivaVentas = $ventasIVA - $ncIVA;
        $creditoTributario = $comprasIVA;
        $impuestoCausado = max(0, $ivaVentas - $creditoTributario);

        return [
            'form_type' => TaxFormType::F104->value,
            'year' => $year,
            'month' => $month,
            'company' => [
                'ruc' => $this->company->ruc,
                'business_name' => $this->company->business_name,
            ],
            'ventas' => [
                'subtotal_0' => round($ventasSubtotal0, 2),
                'subtotal_12' => round($ventasSubtotal12, 2),
                'subtotal_15' => round($ventasSubtotal15, 2),
                'iva_generado' => round($ventasIVA, 2),
            ],
            'notas_credito' => [
                'subtotal' => round($ncSubtotal, 2),
                'iva' => round($ncIVA, 2),
            ],
            'compras' => [
                'subtotal_0' => round($comprasSubtotal0, 2),
                'subtotal_gravado' => round($comprasSubtotalGravado, 2),
                'iva_compras' => round($comprasIVA, 2),
            ],
            'liquidacion' => [
                'iva_ventas' => round($ivaVentas, 2),
                'credito_tributario' => round($creditoTributario, 2),
                'impuesto_causado' => round($impuestoCausado, 2),
            ],
        ];
    }

    /**
     * Formulario 101 - IR Sociedades (Anual)
     */
    public function generateF101(int $year): array
    {
        $accounts = Account::where('company_id', $this->company->id)
            ->whereNotNull('tax_form_code')
            ->where('is_active', true)
            ->orderBy('tax_form_code')
            ->get();

        $casilleros = [];
        $toDate = "{$year}-12-31";

        foreach ($accounts as $account) {
            $balance = abs($account->getBalance("{$year}-01-01", $toDate));
            if ($balance > 0) {
                $casilleros[$account->tax_form_code] = ($casilleros[$account->tax_form_code] ?? 0) + $balance;
            }
        }

        return [
            'form_type' => TaxFormType::F101->value,
            'year' => $year,
            'company' => [
                'ruc' => $this->company->ruc,
                'business_name' => $this->company->business_name,
            ],
            'casilleros' => $casilleros,
        ];
    }

    /**
     * Formulario 102 - IR Personas Naturales (Anual)
     */
    public function generateF102(int $year): array
    {
        // Estructura similar al F101 pero con casilleros diferentes
        return $this->generateF101($year);
    }

    /**
     * Guardar formulario generado
     */
    public function saveSubmission(array $formData, TaxFormType $type): TaxFormSubmission
    {
        return TaxFormSubmission::create([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'form_type' => $type,
            'fiscal_year' => $formData['year'],
            'fiscal_month' => $formData['month'] ?? null,
            'status' => 'generated',
            'generated_data' => $formData,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
        ]);
    }
}
