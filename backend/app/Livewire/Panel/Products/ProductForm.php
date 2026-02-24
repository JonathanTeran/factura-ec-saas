<?php

namespace App\Livewire\Panel\Products;

use App\Models\Tenant\Product;
use App\Models\Tenant\Category;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $type = 'product';
    public ?int $category_id = null;
    public string $main_code = '';
    public string $aux_code = '';
    public string $name = '';
    public string $description = '';
    public string $unit_price = '';
    public string $cost_price = '';
    public string $tax_percentage_code = '2'; // 12% default
    public bool $has_ice = false;
    public string $ice_code = '';
    public bool $track_inventory = true;
    public string $current_stock = '0';
    public string $min_stock = '0';
    public string $unit_of_measure = 'unidad';
    public bool $is_active = true;
    public bool $is_favorite = false;
    public string $barcode = '';
    public $image;

    protected function rules(): array
    {
        $productId = $this->product?->id;

        return [
            'type' => 'required|in:product,service',
            'category_id' => 'nullable|exists:categories,id',
            'main_code' => [
                'required',
                'string',
                'max:25',
                function ($attribute, $value, $fail) use ($productId) {
                    $exists = Product::where('tenant_id', auth()->user()->tenant_id)
                        ->where('main_code', $value)
                        ->when($productId, fn($q) => $q->where('id', '!=', $productId))
                        ->exists();

                    if ($exists) {
                        $fail('Este código ya está en uso.');
                    }
                },
            ],
            'aux_code' => 'nullable|string|max:25',
            'name' => 'required|string|max:300',
            'description' => 'nullable|string|max:1000',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_percentage_code' => 'required|in:0,2,3,4,5,6,7',
            'has_ice' => 'boolean',
            'ice_code' => 'nullable|required_if:has_ice,true|string|max:10',
            'track_inventory' => 'boolean',
            'current_stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_of_measure' => 'required|string|max:50',
            'is_active' => 'boolean',
            'is_favorite' => 'boolean',
            'barcode' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            if ($product->tenant_id !== auth()->user()->tenant_id) {
                abort(403);
            }

            $this->product = $product;

            $this->type = $this->product->type;
            $this->category_id = $this->product->category_id;
            $this->main_code = $this->product->main_code;
            $this->aux_code = $this->product->aux_code ?? '';
            $this->name = $this->product->name;
            $this->description = $this->product->description ?? '';
            $this->unit_price = (string) $this->product->unit_price;
            $this->cost_price = (string) ($this->product->cost_price ?? '');
            $this->tax_percentage_code = $this->product->tax_percentage_code;
            $this->has_ice = $this->product->has_ice;
            $this->ice_code = $this->product->ice_code ?? '';
            $this->track_inventory = $this->product->track_inventory;
            $this->current_stock = (string) ($this->product->current_stock ?? '0');
            $this->min_stock = (string) ($this->product->min_stock ?? '0');
            $this->unit_of_measure = $this->product->unit_of_measure;
            $this->is_active = $this->product->is_active;
            $this->is_favorite = $this->product->is_favorite;
            $this->barcode = $this->product->barcode ?? '';
        } else {
            // Generar código automático
            $lastProduct = Product::where('tenant_id', auth()->user()->tenant_id)
                ->orderByDesc('id')
                ->first();

            $nextCode = $lastProduct
                ? 'PROD-' . str_pad((int) filter_var($lastProduct->main_code, FILTER_SANITIZE_NUMBER_INT) + 1, 4, '0', STR_PAD_LEFT)
                : 'PROD-0001';

            $this->main_code = $nextCode;
        }
    }

    public function updatedType(): void
    {
        if ($this->type === 'service') {
            $this->track_inventory = false;
        }
    }

    public function getCategoriesProperty()
    {
        return Category::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->ordered()
            ->get();
    }

    public function getTaxRatesProperty(): array
    {
        return [
            '0' => 'IVA 0%',
            '5' => 'IVA 5%',
            '2' => 'IVA 12%',
            '4' => 'IVA 15%',
            '6' => 'No Objeto de IVA',
            '7' => 'Exento de IVA',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id' => auth()->user()->tenant_id,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'main_code' => $this->main_code,
            'aux_code' => $this->aux_code ?: null,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'unit_price' => (float) $this->unit_price,
            'cost_price' => $this->cost_price ? (float) $this->cost_price : null,
            'tax_percentage_code' => $this->tax_percentage_code,
            'tax_rate' => $this->getTaxRate(),
            'has_ice' => $this->has_ice,
            'ice_code' => $this->has_ice ? $this->ice_code : null,
            'track_inventory' => $this->type === 'product' && $this->track_inventory,
            'current_stock' => $this->track_inventory ? (float) $this->current_stock : 0,
            'min_stock' => $this->track_inventory ? (float) $this->min_stock : 0,
            'unit_of_measure' => $this->unit_of_measure,
            'is_active' => $this->is_active,
            'is_favorite' => $this->is_favorite,
            'barcode' => $this->barcode ?: null,
        ];

        if ($this->product) {
            $this->product->update($data);
            $product = $this->product;
            $message = 'Producto actualizado correctamente.';
        } else {
            $product = Product::create($data);
            $message = 'Producto creado correctamente.';
        }

        // Guardar imagen
        if ($this->image) {
            $product->addMedia($this->image->getRealPath())
                ->usingFileName($this->image->getClientOriginalName())
                ->toMediaCollection('image');
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->redirect(route('panel.products.index'), navigate: true);
    }

    private function getTaxRate(): float
    {
        return match ($this->tax_percentage_code) {
            '0' => 0.00,
            '5' => 5.00,
            '2' => 12.00,
            '3' => 14.00,
            '4' => 15.00,
            '6', '7' => 0.00,
            default => 15.00,
        };
    }

    public function render()
    {
        return view('livewire.panel.products.product-form', [
            'categories' => $this->categories,
            'taxRates' => $this->taxRates,
        ])->layout('layouts.tenant', [
            'title' => $this->product ? 'Editar Producto' : 'Nuevo Producto',
        ]);
    }
}
