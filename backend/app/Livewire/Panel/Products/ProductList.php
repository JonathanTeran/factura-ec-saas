<?php

namespace App\Livewire\Panel\Products;

use App\Models\Tenant\Product;
use App\Models\SRI\DocumentItem;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';
    public string $category = '';
    public string $stockStatus = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'category' => ['except' => ''],
        'stockStatus' => ['except' => ''],
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
        $this->reset(['search', 'type', 'category', 'stockStatus']);
        $this->resetPage();
    }

    public function getProductsProperty()
    {
        $query = Product::where('tenant_id', auth()->user()->tenant_id)
            ->with('category');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('main_code', 'like', "%{$this->search}%")
                    ->orWhere('aux_code', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            });
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->category) {
            $query->where('category_id', $this->category);
        }

        if ($this->stockStatus) {
            match ($this->stockStatus) {
                'low' => $query->lowStock(),
                'out' => $query->where('track_inventory', true)->where('current_stock', '<=', 0),
                'available' => $query->where('track_inventory', true)->where('current_stock', '>', 0),
                default => null,
            };
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total' => Product::where('tenant_id', $tenantId)->count(),
            'products' => Product::where('tenant_id', $tenantId)->where('type', 'product')->count(),
            'services' => Product::where('tenant_id', $tenantId)->where('type', 'service')->count(),
            'lowStock' => Product::where('tenant_id', $tenantId)->lowStock()->count(),
        ];
    }

    public function getCategoriesProperty()
    {
        return \App\Models\Tenant\Category::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->ordered()
            ->get();
    }

    public function toggleFavorite(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($productId);

        $product->update(['is_favorite' => !$product->is_favorite]);
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($productId);

        $product->update(['is_active' => !$product->is_active]);
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($productId);

        // Verificar si tiene documentos asociados
        $hasDocuments = DocumentItem::where('product_id', $productId)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->exists();

        if ($hasDocuments) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar el producto porque tiene documentos asociados. Puedes desactivarlo en su lugar.',
            ]);
            return;
        }

        $product->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.products.product-list', [
            'products' => $this->products,
            'stats' => $this->stats,
            'categories' => $this->categories,
        ])->layout('layouts.tenant', ['title' => 'Productos']);
    }
}
