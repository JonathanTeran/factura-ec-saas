<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DocumentResource;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    public function stats(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $month    = now()->format('Y-m');

        $data = Cache::tags(["tenant:{$tenantId}", 'dashboard'])
            ->remember("dashboard:stats:{$tenantId}:{$month}", now()->addMinutes(5), function () use ($tenantId) {
                $now               = now();
                $startOfMonth      = $now->copy()->startOfMonth();
                $startOfLastMonth  = $now->copy()->subMonth()->startOfMonth();
                $endOfLastMonth    = $now->copy()->subMonth()->endOfMonth();

                $currentMonthDocs = ElectronicDocument::where('tenant_id', $tenantId)
                    ->whereBetween('issue_date', [$startOfMonth, $now])
                    ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
                    ->first();

                $lastMonthDocs = ElectronicDocument::where('tenant_id', $tenantId)
                    ->whereBetween('issue_date', [$startOfLastMonth, $endOfLastMonth])
                    ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
                    ->first();

                $byStatus = ElectronicDocument::where('tenant_id', $tenantId)
                    ->whereBetween('issue_date', [$startOfMonth, $now])
                    ->groupBy('status')
                    ->selectRaw('status, COUNT(*) as count')
                    ->pluck('count', 'status');

                // Contadores históricos por tipo de comprobante (mosaico del dashboard)
                $byType = ElectronicDocument::where('tenant_id', $tenantId)
                    ->groupBy('document_type')
                    ->selectRaw('document_type, COUNT(*) as count')
                    ->pluck('count', 'document_type');

                $draftsCount = ElectronicDocument::where('tenant_id', $tenantId)
                    ->where('status', 'draft')
                    ->count();

                $receivedCount = \App\Models\Tenant\ReceivedDocument::where('tenant_id', $tenantId)->count();

                return [
                    'current_month' => [
                        'documents_count' => $currentMonthDocs->count ?? 0,
                        'documents_total' => (float) ($currentMonthDocs->total ?? 0),
                    ],
                    'last_month' => [
                        'documents_count' => $lastMonthDocs->count ?? 0,
                        'documents_total' => (float) ($lastMonthDocs->total ?? 0),
                    ],
                    'by_status' => [
                        'authorized' => $byStatus['authorized'] ?? 0,
                        'rejected'   => $byStatus['rejected'] ?? 0,
                        'pending'    => ($byStatus['draft'] ?? 0) + ($byStatus['processing'] ?? 0) + ($byStatus['sent'] ?? 0),
                    ],
                    'by_type' => [
                        'facturas'      => $byType['01'] ?? 0,
                        'liquidaciones' => $byType['03'] ?? 0,
                        'notas_credito' => $byType['04'] ?? 0,
                        'notas_debito'  => $byType['05'] ?? 0,
                        'guias'         => $byType['06'] ?? 0,
                        'retenciones'   => $byType['07'] ?? 0,
                        'borradores'    => $draftsCount,
                        'recibidos'     => $receivedCount,
                    ],
                ];
            });

        // Plan usage siempre fresco (puede cambiar con cada documento emitido)
        // El usuario puede no tener tenant (ej. super admin) -> valores neutros.
        $tenant          = $request->user()->tenant?->load('plan');
        $documentsUsed   = $tenant->documents_issued_this_month ?? 0;
        $documentsLimit  = $tenant?->plan?->max_documents_per_month ?? -1;
        $data['plan_usage'] = [
            'documents_used'  => $documentsUsed,
            'documents_limit' => $documentsLimit,
            'percentage'      => $documentsLimit > 0
                ? round(($documentsUsed / $documentsLimit) * 100, 1)
                : 0,
        ];

        return $this->success($data);
    }

    /**
     * Semáforo "listo para facturar": checklist operativo + vigencia de firma
     * + estado del RUC en el SRI (null si el SRI no responde).
     */
    public function readiness(Request $request): JsonResponse
    {
        $company = $request->user()->tenant->companies()->first();

        if (! $company) {
            return $this->notFound('Aún no has configurado tu empresa.');
        }

        $rucActive = null;
        $taxpayer = app(\App\Services\SRI\RucLookupService::class)->lookup($company->ruc);
        if ($taxpayer !== null) {
            $rucActive = $taxpayer['status'] === 'ACTIVO';
        }

        return $this->success([
            'ready' => $company->isReadyForEmission() && $rucActive !== false,
            'checklist' => $company->emissionReadinessChecklist(),
            'signature_days_remaining' => $company->signatureDaysRemaining(),
            'signature_expiring_soon' => $company->isSignatureExpiringSoon(),
            'sri_environment' => (string) $company->sri_environment,
            'ruc_active' => $rucActive,
        ]);
    }

    public function recentDocuments(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $documents = Cache::tags(["tenant:{$tenantId}", 'dashboard'])
            ->remember("dashboard:recent:{$tenantId}", now()->addMinutes(2), function () use ($tenantId) {
                return ElectronicDocument::where('tenant_id', $tenantId)
                    ->with(['customer', 'company'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            });

        return $this->success([
            'documents' => DocumentResource::collection($documents),
        ]);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $year     = now()->year;

        $monthlyData = Cache::tags(["tenant:{$tenantId}", 'dashboard'])
            ->remember("dashboard:monthly:{$tenantId}:{$year}", now()->addMinutes(15), function () use ($tenantId, $year) {
                $summary = ElectronicDocument::where('tenant_id', $tenantId)
                    ->where('status', 'authorized')
                    ->whereYear('issue_date', $year)
                    ->groupBy(DB::raw('MONTH(issue_date)'))
                    ->selectRaw('MONTH(issue_date) as month, COUNT(*) as count, SUM(total) as total')
                    ->orderBy('month')
                    ->get()
                    ->keyBy('month');

                $data = [];
                for ($i = 1; $i <= 12; $i++) {
                    $data[] = [
                        'month'      => $i,
                        'month_name' => \Carbon\Carbon::create()->month($i)->translatedFormat('M'),
                        'count'      => $summary[$i]->count ?? 0,
                        'total'      => (float) ($summary[$i]->total ?? 0),
                    ];
                }

                return $data;
            });

        return $this->success([
            'year'    => $year,
            'monthly' => $monthlyData,
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $days     = (int) $request->input('days', 30);

        $result = Cache::tags(["tenant:{$tenantId}", 'dashboard'])
            ->remember("dashboard:chart:{$tenantId}:{$days}", now()->addMinutes(5), function () use ($tenantId, $days) {
                $startDate = now()->subDays($days)->startOfDay();

                $dailyData = ElectronicDocument::where('tenant_id', $tenantId)
                    ->where('status', 'authorized')
                    ->where('issue_date', '>=', $startDate)
                    ->groupBy(DB::raw('DATE(issue_date)'))
                    ->selectRaw('DATE(issue_date) as date, COUNT(*) as count, SUM(total) as total')
                    ->orderBy('date')
                    ->get();

                $byType = ElectronicDocument::where('tenant_id', $tenantId)
                    ->where('status', 'authorized')
                    ->where('issue_date', '>=', $startDate)
                    ->groupBy('document_type')
                    ->selectRaw('document_type, COUNT(*) as count, SUM(total) as total')
                    ->get()
                    ->map(fn ($item) => [
                        'type'  => $item->document_type,
                        'label' => $item->document_type->label(),
                        'count' => $item->count,
                        'total' => (float) $item->total,
                    ]);

                return [
                    'daily'   => $dailyData->map(fn ($item) => [
                        'date'  => $item->date,
                        'count' => $item->count,
                        'total' => (float) $item->total,
                    ]),
                    'by_type' => $byType,
                ];
            });

        return $this->success($result);
    }
}
