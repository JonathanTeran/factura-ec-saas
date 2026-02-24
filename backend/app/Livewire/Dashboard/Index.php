<?php

namespace App\Livewire\Dashboard;

use App\Enums\DocumentStatus;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public array $stats = [];
    public array $recentDocuments = [];
    public array $monthlyData = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadRecentDocuments();
        $this->loadMonthlyData();
    }

    protected function loadStats(): void
    {
        $tenantId = auth()->user()->tenant_id;
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        // Current month totals
        $currentMonth = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$startOfMonth, $now])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        // Last month for comparison
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $lastMonth = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        // Pending documents
        $pending = ElectronicDocument::where('tenant_id', $tenantId)
            ->whereIn('status', [DocumentStatus::DRAFT, DocumentStatus::PROCESSING, DocumentStatus::SENT])
            ->count();

        // Rejected documents
        $rejected = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', DocumentStatus::REJECTED)
            ->whereBetween('issue_date', [$startOfMonth, $now])
            ->count();

        $this->stats = [
            'documents_count' => $currentMonth->count ?? 0,
            'documents_total' => (float) ($currentMonth->total ?? 0),
            'documents_count_last' => $lastMonth->count ?? 0,
            'documents_total_last' => (float) ($lastMonth->total ?? 0),
            'pending' => $pending,
            'rejected' => $rejected,
        ];
    }

    protected function loadRecentDocuments(): void
    {
        $this->recentDocuments = ElectronicDocument::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer:id,name', 'company:id,trade_name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'document_number' => $doc->document_number,
                'customer_name' => $doc->customer?->name ?? 'Consumidor Final',
                'total' => $doc->total,
                'status' => $doc->status->value,
                'status_label' => $doc->status->label(),
                'status_color' => $doc->status->color(),
                'issue_date' => $doc->issue_date?->format('d/m/Y'),
            ])
            ->toArray();
    }

    protected function loadMonthlyData(): void
    {
        $tenantId = auth()->user()->tenant_id;

        $data = ElectronicDocument::where('tenant_id', $tenantId)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereYear('issue_date', now()->year)
            ->groupBy(DB::raw('MONTH(issue_date)'))
            ->selectRaw('MONTH(issue_date) as month, COUNT(*) as count, SUM(total) as total')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $this->monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $this->monthlyData[] = [
                'month' => \Carbon\Carbon::create()->month($i)->translatedFormat('M'),
                'count' => $data[$i]->count ?? 0,
                'total' => (float) ($data[$i]->total ?? 0),
            ];
        }
    }

    public function render()
    {
        return view('livewire.dashboard.index')
            ->layout('layouts.tenant', ['title' => 'Dashboard']);
    }
}
