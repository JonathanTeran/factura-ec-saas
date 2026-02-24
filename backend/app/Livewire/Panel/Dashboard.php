<?php

namespace App\Livewire\Panel;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\SRI\ElectronicDocument as Document;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'month';

    public function mount(): void
    {
        //
    }

    public function getStatsProperty(): array
    {
        $tenant = auth()->user()->tenant;
        $startDate = $this->getStartDate();

        // Documents this period
        $documentsThisPeriod = Document::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $documentsPreviousPeriod = Document::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$this->getPreviousStartDate(), $startDate])
            ->count();

        $documentsChange = $documentsPreviousPeriod > 0
            ? round((($documentsThisPeriod - $documentsPreviousPeriod) / $documentsPreviousPeriod) * 100, 1)
            : 0;

        // Revenue this period
        $revenueThisPeriod = Document::where('tenant_id', $tenant->id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->where('created_at', '>=', $startDate)
            ->sum('total');

        $revenuePreviousPeriod = Document::where('tenant_id', $tenant->id)
            ->where('document_type', DocumentType::FACTURA)
            ->where('status', DocumentStatus::AUTHORIZED)
            ->whereBetween('created_at', [$this->getPreviousStartDate(), $startDate])
            ->sum('total');

        $revenueChange = $revenuePreviousPeriod > 0
            ? round((($revenueThisPeriod - $revenuePreviousPeriod) / $revenuePreviousPeriod) * 100, 1)
            : 0;

        // Customers
        $customersThisPeriod = Customer::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalCustomers = Customer::where('tenant_id', $tenant->id)->count();

        // Pending documents
        $pendingDocuments = Document::where('tenant_id', $tenant->id)
            ->whereIn('status', [DocumentStatus::DRAFT, DocumentStatus::PROCESSING, DocumentStatus::SENT])
            ->count();

        return [
            'documents' => [
                'value' => $documentsThisPeriod,
                'change' => $documentsChange,
                'changeType' => $documentsChange >= 0 ? 'positive' : 'negative',
            ],
            'revenue' => [
                'value' => $revenueThisPeriod,
                'change' => $revenueChange,
                'changeType' => $revenueChange >= 0 ? 'positive' : 'negative',
            ],
            'customers' => [
                'value' => $totalCustomers,
                'new' => $customersThisPeriod,
            ],
            'pending' => [
                'value' => $pendingDocuments,
            ],
        ];
    }

    public function getRecentDocumentsProperty()
    {
        return Document::where('tenant_id', auth()->user()->tenant_id)
            ->with(['customer'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function getDocumentsByStatusProperty(): array
    {
        $statuses = Document::where('tenant_id', auth()->user()->tenant_id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'authorized' => $statuses[DocumentStatus::AUTHORIZED->value] ?? 0,
            'pending' => ($statuses[DocumentStatus::PROCESSING->value] ?? 0) + ($statuses[DocumentStatus::SENT->value] ?? 0),
            'draft' => $statuses[DocumentStatus::DRAFT->value] ?? 0,
            'rejected' => $statuses[DocumentStatus::REJECTED->value] ?? 0,
        ];
    }

    public function getChartDataProperty(): array
    {
        $tenant = auth()->user()->tenant;
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data['labels'][] = $date->format('d M');

            $data['invoices'][] = Document::where('tenant_id', $tenant->id)
                ->where('document_type', DocumentType::FACTURA)
                ->whereDate('created_at', $date)
                ->count();

            $data['revenue'][] = Document::where('tenant_id', $tenant->id)
                ->where('document_type', DocumentType::FACTURA)
                ->where('status', DocumentStatus::AUTHORIZED)
                ->whereDate('created_at', $date)
                ->sum('total');
        }

        return $data;
    }

    public function getTopCustomersProperty()
    {
        return Customer::where('tenant_id', auth()->user()->tenant_id)
            ->withSum(['electronicDocuments' => fn($q) => $q->where('status', DocumentStatus::AUTHORIZED)], 'total')
            ->orderByDesc('electronic_documents_sum_total')
            ->take(5)
            ->get();
    }

    public function getTopProductsProperty()
    {
        return Product::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('documentItems')
            ->orderByDesc('document_items_count')
            ->take(5)
            ->get();
    }

    protected function getStartDate(): Carbon
    {
        return match ($this->period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    protected function getPreviousStartDate(): Carbon
    {
        return match ($this->period) {
            'week' => now()->subWeek()->startOfWeek(),
            'month' => now()->subMonth()->startOfMonth(),
            'year' => now()->subYear()->startOfYear(),
            default => now()->subMonth()->startOfMonth(),
        };
    }

    public function render()
    {
        return view('livewire.panel.dashboard')
            ->layout('layouts.tenant', ['title' => 'Dashboard']);
    }
}
