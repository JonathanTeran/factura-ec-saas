<?php

namespace App\Livewire\Panel\Purchases;

use App\Enums\PurchaseStatus;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $supplierId = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'issue_date';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'supplierId' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'supplierId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getPurchasesProperty()
    {
        $query = Purchase::where('tenant_id', auth()->user()->tenant_id)
            ->with(['supplier', 'company']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('supplier_document_number', 'like', "%{$this->search}%")
                    ->orWhereHas('supplier', fn($q) => $q->search($this->search));
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }

        if ($this->dateFrom) {
            $query->where('issue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('issue_date', '<=', $this->dateTo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $thisMonth = now()->startOfMonth();

        return [
            'total' => Purchase::where('tenant_id', $tenantId)->count(),
            'this_month' => Purchase::where('tenant_id', $tenantId)
                ->where('issue_date', '>=', $thisMonth)
                ->count(),
            'total_amount_month' => (float) Purchase::where('tenant_id', $tenantId)
                ->where('issue_date', '>=', $thisMonth)
                ->where('status', '!=', PurchaseStatus::VOIDED)
                ->sum('total'),
            'pending_withholding' => Purchase::where('tenant_id', $tenantId)
                ->where('status', PurchaseStatus::REGISTERED)
                ->count(),
        ];
    }

    public function getSuppliersProperty()
    {
        return Supplier::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->orderBy('business_name')
            ->get();
    }

    public function voidPurchase(int $purchaseId): void
    {
        $purchase = Purchase::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($purchaseId);

        if ($purchase->status === PurchaseStatus::VOIDED) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Esta compra ya esta anulada.',
            ]);
            return;
        }

        $purchase->update(['status' => PurchaseStatus::VOIDED]);
        $purchase->supplier->decrement('total_purchased', $purchase->total);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Compra anulada correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.purchases.purchase-list', [
            'purchases' => $this->purchases,
            'stats' => $this->stats,
            'suppliers' => $this->suppliers,
            'statuses' => PurchaseStatus::cases(),
        ])->layout('layouts.tenant', ['title' => 'Compras']);
    }
}
