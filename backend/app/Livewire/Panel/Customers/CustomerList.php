<?php

namespace App\Livewire\Panel\Customers;

use App\Models\Tenant\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
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
        $this->reset(['search', 'type']);
        $this->resetPage();
    }

    public function getCustomersProperty()
    {
        $sortField = $this->sortField === 'business_name' ? 'name' : $this->sortField;

        $query = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('documents')
            ->withSum(['documents' => fn($q) => $q->where('status', 'authorized')], 'total');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('identification', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->type) {
            $query->where('identification_type', $this->type);
        }

        return $query->orderBy($sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total' => Customer::where('tenant_id', $tenantId)->count(),
            'thisMonth' => Customer::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'withDocuments' => Customer::where('tenant_id', $tenantId)
                ->whereHas('documents')
                ->count(),
        ];
    }

    public function deleteCustomer(int $customerId): void
    {
        $customer = Customer::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($customerId);

        if ($customer->documents()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar un cliente con documentos asociados.',
            ]);
            return;
        }

        $customer->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.customers.customer-list', [
            'customers' => $this->customers,
            'stats' => $this->stats,
        ])->layout('layouts.tenant', ['title' => 'Clientes']);
    }
}
