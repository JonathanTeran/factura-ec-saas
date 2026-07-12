<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Report\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends ApiController
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $stats = $this->reportService->forTenant($tenant)->getDashboardStats();

        return $this->success($stats);
    }

    public function sales(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();
        $groupBy = $request->input('group_by', 'day');

        $report = $this->reportService
            ->forTenant($tenant)
            ->getSalesReport($from, $to, $groupBy);

        return $this->success($report);
    }

    public function taxes(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();

        $report = $this->reportService
            ->forTenant($tenant)
            ->getTaxReport($from, $to);

        return $this->success($report);
    }

    /**
     * Resumen mensual de IVA (ventas, notas de crédito y compras RUC/cédula).
     * Acepta year+month o el rango from/to.
     */
    public function taxSummary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        [$from, $to] = $this->resolvePeriod($request);

        $summary = $this->reportService
            ->forTenant($request->user()->tenant)
            ->getMonthlyTaxSummary($from, $to);

        return $this->success($summary);
    }

    /**
     * Exporta el reporte de ventas a Excel para el período dado.
     */
    public function salesExport(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        [$from, $to] = $this->resolvePeriod($request);

        $data = $this->reportService
            ->forTenant($request->user()->tenant)
            ->getSalesReport($from, $to, 'month');

        $filename = 'ventas_'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ReportExport('sales', $data, $from->toDateString(), $to->toDateString()),
            $filename,
        );
    }

    /**
     * Resuelve el período: prioriza year+month; si no, usa from/to; si no, el
     * mes en curso.
     */
    private function resolvePeriod(Request $request): array
    {
        if ($request->filled('year') && $request->filled('month')) {
            $from = Carbon::create((int) $request->year, (int) $request->month, 1)->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } elseif ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();
        } else {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
        }

        return [$from, $to];
    }

    public function topCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();
        $limit = $request->input('limit', 10);

        $customers = $this->reportService
            ->forTenant($tenant)
            ->getTopCustomers($from, $to, $limit);

        return $this->success(['customers' => $customers]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();
        $limit = $request->input('limit', 10);

        $products = $this->reportService
            ->forTenant($tenant)
            ->getTopProducts($from, $to, $limit);

        return $this->success(['products' => $products]);
    }

    public function documentsByStatus(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();

        $statuses = $this->reportService
            ->forTenant($tenant)
            ->getDocumentsByStatus($from, $to);

        return $this->success(['statuses' => $statuses]);
    }

    public function comparison(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();

        $comparison = $this->reportService
            ->forTenant($tenant)
            ->getPeriodComparison($from, $to);

        return $this->success($comparison);
    }

    public function withholdings(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $tenant = $request->user()->tenant;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to)->endOfDay();

        $withholdings = $this->reportService
            ->forTenant($tenant)
            ->getWithholdingsReport($from, $to);

        return $this->success(['withholdings' => $withholdings]);
    }

    public function ats(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . now()->year,
            'month' => 'required|integer|min:1|max:12',
        ]);

        $tenant = $request->user()->tenant;

        $atsData = $this->reportService
            ->forTenant($tenant)
            ->getATSData($request->year, $request->month);

        return $this->success([
            'ats' => $atsData,
            'period' => [
                'year' => $request->year,
                'month' => $request->month,
            ],
        ]);
    }
}
