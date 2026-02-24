<?php

namespace App\Livewire\Products;

use App\Models\Tenant\Product;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $type = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(Product $product): void
    {
        if ($product->tenant_id !== auth()->user()->tenant_id) {
            session()->flash('error', 'No tienes permiso para eliminar este producto.');
            return;
        }

        if ($product->documentItems()->exists()) {
            session()->flash('error', 'No se puede eliminar el producto porque está asociado a documentos.');
            return;
        }

        $product->delete();
        session()->flash('success', 'Producto eliminado exitosamente.');
    }

    public function render()
    {
        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.products.index', compact('products'))
            ->layout('layouts.tenant', ['title' => 'Productos']);
    }
}
