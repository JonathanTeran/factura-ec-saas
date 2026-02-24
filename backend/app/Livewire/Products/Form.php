<?php

namespace App\Livewire\Products;

use App\Models\Tenant\Product;
use Livewire\Component;

class Form extends Component
{
    public ?Product $product = null;

    public string $code = '';
    public string $sku = '';
    public string $name = '';
    public string $description = '';
    public string $type = 'product';
    public float $unit_price = 0;
    public float $cost = 0;
    public string $tax_code = '2';
    public string $tax_percentage_code = '2';
    public float $tax_rate = 12;
    public bool $track_inventory = false;
    public int $stock = 0;
    public int $min_stock = 0;
    public bool $is_active = true;

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            if ($product->tenant_id !== auth()->user()->tenant_id) {
                abort(403);
            }

            $this->product = $product;
            $this->code = $product->code;
            $this->sku = $product->sku ?? '';
            $this->name = $product->name;
            $this->description = $product->description ?? '';
            $this->type = $product->type;
            $this->unit_price = (float) $product->unit_price;
            $this->cost = (float) ($product->cost ?? 0);
            $this->tax_code = $product->tax_code ?? '2';
            $this->tax_percentage_code = $product->tax_percentage_code ?? '2';
            $this->tax_rate = (float) ($product->tax_rate ?? 12);
            $this->track_inventory = $product->track_inventory;
            $this->stock = (int) ($product->stock ?? 0);
            $this->min_stock = (int) ($product->min_stock ?? 0);
            $this->is_active = $product->is_active;
        }
    }

    protected function rules(): array
    {
        $productId = $this->product?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                "unique:products,code,{$productId},id,tenant_id," . auth()->user()->tenant_id,
            ],
            'sku' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:product,service'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'tax_code' => ['nullable', 'string', 'max:5'],
            'tax_percentage_code' => ['nullable', 'string', 'max:5'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'track_inventory' => ['boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'code.required' => 'El código es requerido.',
            'code.unique' => 'Este código ya está registrado.',
            'name.required' => 'El nombre es requerido.',
            'type.required' => 'El tipo es requerido.',
            'unit_price.required' => 'El precio unitario es requerido.',
            'unit_price.numeric' => 'El precio unitario debe ser un número.',
            'unit_price.min' => 'El precio unitario no puede ser negativo.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->product) {
            $this->product->update($validated);
            session()->flash('success', 'Producto actualizado exitosamente.');
        } else {
            Product::create([
                'tenant_id' => auth()->user()->tenant_id,
                ...$validated,
            ]);
            session()->flash('success', 'Producto creado exitosamente.');
        }

        $this->redirect(route('tenant.products.index'));
    }

    public function render()
    {
        return view('livewire.products.form', [
            'isEditing' => $this->product !== null,
        ])->layout('layouts.tenant', [
            'title' => $this->product ? 'Editar Producto' : 'Nuevo Producto',
        ]);
    }
}
