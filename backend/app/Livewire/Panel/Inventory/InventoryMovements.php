<?php

namespace App\Livewire\Panel\Inventory;

use App\Models\Tenant\InventoryMovement;
use App\Models\Tenant\Product;
use App\Enums\MovementType;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryMovements extends Component
{
    use WithPagination;

    public string $search = '';
    public string $movementType = '';
    public string $productFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'movementType' => ['except' => ''],
        'productFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
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
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'movementType', 'productFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getMovementsProperty()
    {
        return InventoryMovement::where('inventory_movements.tenant_id', auth()->user()->tenant_id)
            ->with(['product', 'createdBy'])
            ->when($this->search, fn($q) => $q->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('main_code', 'like', "%{$this->search}%")
            ))
            ->when($this->movementType, fn($q) => $q->where('movement_type', $this->movementType))
            ->when($this->productFilter, fn($q) => $q->where('product_id', $this->productFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);
    }

    public function getProductsProperty()
    {
        return Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('track_inventory', true)
            ->orderBy('name')
            ->get(['id', 'name', 'main_code']);
    }

    public function getMovementTypesProperty(): array
    {
        return collect(MovementType::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->toArray();
    }

    public function render()
    {
        return view('livewire.panel.inventory.inventory-movements', [
            'movements' => $this->movements,
            'products' => $this->products,
            'movementTypes' => $this->movementTypes,
        ])->layout('layouts.tenant', ['title' => 'Movimientos de Inventario']);
    }
}
