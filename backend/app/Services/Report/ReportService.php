<?php

namespace App\Services\Report;

use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use App\Services\Purchase\PurchaseService;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ReportService
{
    private Tenant $tenant;

    public function forTenant(Tenant $tenant): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * Dashboard stats generales.
     */
    public function getDashboardStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'documents' => [
                'this_month' => $this->getDocumentStats($thisMonth, now()),
                'last_month' => $this->getDocumentStats($lastMonth, $thisMonth),
            ],
            'revenue' => [
                'this_month' => $this->getRevenueStats($thisMonth, now()),
                'last_month' => $this->getRevenueStats($lastMonth, $thisMonth),
            ],
            'customers' => [
                'total' => Customer::where('tenant_id', $this->tenant->id)->count(),
                'new_this_month' => Customer::where('tenant_id', $this->tenant->id)
                    ->where('created_at', '>=', $thisMonth)
                    ->count(),
            ],
            'products' => [
                'total' => Product::where('tenant_id', $this->tenant->id)->count(),
                'low_stock' => Product::where('tenant_id', $this->tenant->id)->lowStock()->count(),
            ],
        ];
    }

    /**
     * Reporte de ventas por período.
     */
    public function getSalesReport(Carbon $from, Carbon $to, string $groupBy = 'day'): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $dateExpression = match ($groupBy) {
                'month' => "strftime('%Y-%m', issue_date)",
                'week' => "strftime('%Y-%W', issue_date)",
                default => "strftime('%Y-%m-%d', issue_date)",
            };
        } else {
            $dateFormat = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%Y-%W',
                default => '%Y-%m-%d',
            };
            $dateExpression = "DATE_FORMAT(issue_date, '{$dateFormat}')";
        }

        $data = ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$from, $to])
            ->select(
                DB::raw("{$dateExpression} as period"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total'),
                DB::raw('SUM(total_tax) as tax'),
                DB::raw('AVG(total) as average')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $totals = [
            'count' => $data->sum('count'),
            'total' => $data->sum('total'),
            'tax' => $data->sum('tax'),
            'average' => $data->avg('average'),
        ];

        return [
            'data' => $data,
            'totals' => $totals,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'group_by' => $groupBy,
        ];
    }

    /**
     * Reporte de documentos por estado.
     */
    public function getDocumentsByStatus(Carbon $from, Carbon $to): array
    {
        return ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status->value => $item->count])
            ->toArray();
    }

    /**
     * Top clientes por facturación.
     */
    public function getTopCustomers(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return Customer::where('customers.tenant_id', $this->tenant->id)
            ->join('electronic_documents', 'customers.id', '=', 'electronic_documents.customer_id')
            ->where('electronic_documents.document_type', DocumentType::FACTURA)
            ->where('electronic_documents.status', DocumentStatus::AUTHORIZED)
            ->whereBetween('electronic_documents.issue_date', [$from, $to])
            ->select(
                'customers.id',
                'customers.name',
                'customers.identification',
                DB::raw('COUNT(electronic_documents.id) as document_count'),
                DB::raw('SUM(electronic_documents.total) as total_amount')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.identification')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Top productos más vendidos.
     */
    public function getTopProducts(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return Product::where('products.tenant_id', $this->tenant->id)
            ->join('document_items', 'products.id', '=', 'document_items.product_id')
            ->join('electronic_documents', 'document_items.electronic_document_id', '=', 'electronic_documents.id')
            ->where('electronic_documents.document_type', DocumentType::FACTURA)
            ->where('electronic_documents.status', DocumentStatus::AUTHORIZED)
            ->whereBetween('electronic_documents.issue_date', [$from, $to])
            ->select(
                'products.id',
                'products.name',
                'products.main_code',
                DB::raw('SUM(document_items.quantity) as quantity_sold'),
                DB::raw('SUM(document_items.total) as total_amount')
            )
            ->groupBy('products.id', 'products.name', 'products.main_code')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Reporte de impuestos (IVA).
     */
    public function getTaxReport(Carbon $from, Carbon $to): array
    {
        $invoices = ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$from, $to])
            ->select(
                DB::raw('SUM(subtotal_0) as subtotal_0'),
                DB::raw('SUM(subtotal_5) as subtotal_5'),
                DB::raw('SUM(subtotal_12) as subtotal_12'),
                DB::raw('SUM(subtotal_15) as subtotal_15'),
                DB::raw('SUM(subtotal_no_tax) as subtotal_no_tax'),
                DB::raw('SUM(total_tax) as total_tax'),
                DB::raw('SUM(total) as total')
            )
            ->first();

        return [
            'subtotals' => [
                '0%' => (float) $invoices->subtotal_0,
                '5%' => (float) $invoices->subtotal_5,
                '12%' => (float) $invoices->subtotal_12,
                '15%' => (float) $invoices->subtotal_15,
                'no_objeto' => (float) $invoices->subtotal_no_tax,
            ],
            'total_tax' => (float) $invoices->total_tax,
            'total' => (float) $invoices->total,
        ];
    }

    /**
     * Reporte de retenciones recibidas.
     */
    public function getWithholdingsReport(Carbon $from, Carbon $to): array
    {
        return ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('document_type', DocumentType::RETENCION)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$from, $to])
            ->with('withholdingDetails')
            ->get()
            ->flatMap(fn($doc) => $doc->withholdingDetails)
            ->groupBy('tax_type')
            ->map(function ($details, $taxType) {
                return [
                    'tax_type' => $taxType,
                    'count' => $details->count(),
                    'base_total' => $details->sum('base_amount'),
                    'withheld_total' => $details->sum('withheld_amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Comparativa con período anterior.
     */
    public function getPeriodComparison(Carbon $from, Carbon $to): array
    {
        $daysDiff = $from->diffInDays($to);
        $previousFrom = $from->copy()->subDays($daysDiff);
        $previousTo = $from->copy()->subDay();

        $current = $this->getDocumentStats($from, $to);
        $previous = $this->getDocumentStats($previousFrom, $previousTo);

        return [
            'current' => $current,
            'previous' => $previous,
            'changes' => [
                'count' => $this->calculateChange($previous['count'], $current['count']),
                'total' => $this->calculateChange($previous['total'], $current['total']),
            ],
        ];
    }

    /**
     * Exportar datos para ATS (Anexo Transaccional Simplificado).
     */
    public function getATSData(int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        return [
            'ventas' => $this->getATSVentas($from, $to),
            'compras' => (new PurchaseService())->forTenant($this->tenant)->getATSCompras($from, $to),
            'retenciones_emitidas' => $this->getATSRetenciones($from, $to),
            'anulados' => $this->getATSAnulados($from, $to),
        ];
    }

    // ==================== MÉTODOS PRIVADOS ====================

    private function getDocumentStats(Carbon $from, Carbon $to): array
    {
        $stats = ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$from, $to])
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total), 0) as total')
            )
            ->first();

        return [
            'count' => (int) $stats->count,
            'total' => (float) $stats->total,
        ];
    }

    private function getRevenueStats(Carbon $from, Carbon $to): array
    {
        return [
            'invoices' => (float) ElectronicDocument::where('tenant_id', $this->tenant->id)
                ->where('document_type', DocumentType::FACTURA)
                ->where('status', DocumentStatus::AUTHORIZED)
                ->whereBetween('issue_date', [$from, $to])
                ->sum('total'),
            'credit_notes' => (float) ElectronicDocument::where('tenant_id', $this->tenant->id)
                ->where('document_type', DocumentType::NOTA_CREDITO)
                ->where('status', DocumentStatus::AUTHORIZED)
                ->whereBetween('issue_date', [$from, $to])
                ->sum('total'),
        ];
    }

    private function calculateChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function getATSVentas(Carbon $from, Carbon $to): array
    {
        return ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$from, $to])
            ->with('customer')
            ->get()
            ->map(fn($doc) => [
                'tpIdCliente' => $doc->customer->identification_type,
                'idCliente' => $doc->customer->identification,
                'parteRelVtas' => 'NO',
                'tipoComprobante' => '18', // Factura
                'tipoEmision' => 'F',
                'numeroComprobantes' => 1,
                'baseNoGraIva' => $doc->subtotal_no_tax,
                'baseImponible' => $doc->subtotal_0,
                'baseImpGrav' => $doc->subtotal_12 + $doc->subtotal_15,
                'montoIva' => $doc->total_tax,
                'valorRetIva' => 0,
                'valorRetRenta' => 0,
            ])
            ->toArray();
    }

    private function getATSRetenciones(Carbon $from, Carbon $to): array
    {
        return ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('document_type', DocumentType::RETENCION)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$from, $to])
            ->with(['customer', 'withholdingDetails'])
            ->get()
            ->map(fn($doc) => [
                'tpIdProv' => $doc->customer->identification_type,
                'idProv' => $doc->customer->identification,
                'tipoComprobante' => '07',
                'establecimiento' => $doc->branch->code,
                'puntoEmision' => $doc->emissionPoint->code,
                'secuencial' => str_pad($doc->sequential, 9, '0', STR_PAD_LEFT),
                'fechaEmision' => $doc->issue_date->format('d/m/Y'),
                'autorizacion' => $doc->authorization_number,
                'detalles' => $doc->withholdingDetails->map(fn($d) => [
                    'codRetAir' => $d->withholding_code,
                    'baseImpAir' => $d->base_amount,
                    'porcentajeAir' => $d->withholding_percentage,
                    'valRetAir' => $d->withheld_amount,
                ])->toArray(),
            ])
            ->toArray();
    }

    private function getATSAnulados(Carbon $from, Carbon $to): array
    {
        return ElectronicDocument::where('tenant_id', $this->tenant->id)
            ->where('status', DocumentStatus::VOIDED)
            ->whereBetween('updated_at', [$from, $to])
            ->get()
            ->map(fn($doc) => [
                'tipoComprobante' => $doc->document_type->value,
                'establecimiento' => $doc->branch->code,
                'puntoEmision' => $doc->emissionPoint->code,
                'secuencialInicio' => $doc->sequential,
                'secuencialFin' => $doc->sequential,
                'autorizacion' => $doc->authorization_number ?? '',
            ])
            ->toArray();
    }
}
