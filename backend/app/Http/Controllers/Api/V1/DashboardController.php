<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DocumentResource;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    public function stats(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Current month stats
        $currentMonthDocs = ElectronicDocument::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startOfMonth, $now])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        // Last month stats for comparison
        $lastMonthDocs = ElectronicDocument::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startOfLastMonth, $endOfLastMonth])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        // Documents by status
        $byStatus = ElectronicDocument::where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$startOfMonth, $now])
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->pluck('count', 'status');

        // Plan usage
        $tenant = $request->user()->tenant->load('plan');
        $documentsUsed = $tenant->documents_issued_this_month ?? 0;
        $documentsLimit = $tenant->plan->max_documents_per_month ?? -1;

        return $this->success([
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
                'rejected' => $byStatus['rejected'] ?? 0,
                'pending' => ($byStatus['draft'] ?? 0) + ($byStatus['processing'] ?? 0) + ($byStatus['sent'] ?? 0),
            ],
            'plan_usage' => [
                'documents_used' => $documentsUsed,
                'documents_limit' => $documentsLimit,
                'percentage' => $documentsLimit > 0
                    ? round(($documentsUsed / $documentsLimit) * 100, 1)
                    : 0,
            ],
        ]);
    }

    public function recentDocuments(Request $request): JsonResponse
    {
        $documents = ElectronicDocument::where('tenant_id', $request->user()->tenant_id)
            ->with(['customer', 'company'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return $this->success([
            'documents' => DocumentResource::collection($documents),
        ]);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $summary = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', 'authorized')
            ->whereYear('issue_date', now()->year)
            ->groupBy(DB::raw('MONTH(issue_date)'))
            ->selectRaw('MONTH(issue_date) as month, COUNT(*) as count, SUM(total) as total')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[] = [
                'month' => $i,
                'month_name' => \Carbon\Carbon::create()->month($i)->translatedFormat('M'),
                'count' => $summary[$i]->count ?? 0,
                'total' => (float) ($summary[$i]->total ?? 0),
            ];
        }

        return $this->success([
            'year' => now()->year,
            'monthly' => $monthlyData,
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days)->startOfDay();

        $dailyData = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', 'authorized')
            ->where('issue_date', '>=', $startDate)
            ->groupBy(DB::raw('DATE(issue_date)'))
            ->selectRaw('DATE(issue_date) as date, COUNT(*) as count, SUM(total) as total')
            ->orderBy('date')
            ->get();

        // By document type
        $byType = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', 'authorized')
            ->where('issue_date', '>=', $startDate)
            ->groupBy('document_type')
            ->selectRaw('document_type, COUNT(*) as count, SUM(total) as total')
            ->get()
            ->map(fn($item) => [
                'type' => $item->document_type,
                'label' => $item->document_type->label(),
                'count' => $item->count,
                'total' => (float) $item->total,
            ]);

        return $this->success([
            'daily' => $dailyData->map(fn($item) => [
                'date' => $item->date,
                'count' => $item->count,
                'total' => (float) $item->total,
            ]),
            'by_type' => $byType,
        ]);
    }
}
