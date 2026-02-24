<?php

namespace App\Livewire\Panel\Purchases;

use App\Models\Tenant\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'business_name';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
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

    public function getSuppliersProperty()
    {
        $query = Supplier::where('tenant_id', auth()->user()->tenant_id);

        if ($this->search) {
            $query->search($this->search);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function toggleActive(int $supplierId): void
    {
        $supplier = Supplier::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($supplierId);

        $supplier->update(['is_active' => !$supplier->is_active]);
    }

    public function deleteSupplier(int $supplierId): void
    {
        $supplier = Supplier::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($supplierId);

        if ($supplier->purchases()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar el proveedor porque tiene compras registradas.',
            ]);
            return;
        }

        $supplier->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.purchases.supplier-list', [
            'suppliers' => $this->suppliers,
        ])->layout('layouts.tenant', ['title' => 'Proveedores']);
    }
}
