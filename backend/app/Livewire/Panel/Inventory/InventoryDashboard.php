<?php

namespace App\Livewire\Panel\Inventory;

use App\Models\Tenant\Product;
use App\Models\Tenant\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InventoryDashboard extends Component
{
    // Adjustment modal
    public bool $showAdjustModal = false;
    public ?int $adjustProductId = null;
    public string $adjustProductName = '';
    public string $adjustCurrentStock = '';
    public string $adjustNewStock = '';
    public string $adjustReason = '';

    // Purchase modal
    public bool $showPurchaseModal = false;
    public ?int $purchaseProductId = null;
    public string $purchaseProductName = '';
    public string $purchaseQuantity = '';
    public string $purchaseUnitCost = '';
    public string $purchaseBatchNumber = '';
    public string $purchaseExpiryDate = '';
    public string $purchaseNotes = '';

    public function getSummaryProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $totalTracked = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)->count();

        $lowStock = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->where('current_stock', '>', 0)
            ->count();

        $outOfStock = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)
            ->where('current_stock', '<=', 0)
            ->count();

        $totalValue = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)
            ->where('current_stock', '>', 0)
            ->selectRaw('SUM(current_stock * unit_price) as value')
            ->value('value') ?? 0;

        return [
            'tracked' => $totalTracked,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_value' => (float) $totalValue,
        ];
    }

    public function getLowStockProductsProperty()
    {
        return Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('track_inventory', true)
            ->where(function ($q) {
                $q->whereColumn('current_stock', '<=', 'min_stock')
                    ->orWhere('current_stock', '<=', 0);
            })
            ->orderBy('current_stock')
            ->limit(20)
            ->get();
    }

    public function getRecentMovementsProperty()
    {
        return InventoryMovement::where('tenant_id', auth()->user()->tenant_id)
            ->with(['product', 'createdBy'])
            ->latest()
            ->limit(10)
            ->get();
    }

    public function openAdjustModal(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)->findOrFail($productId);
        $this->adjustProductId = $product->id;
        $this->adjustProductName = $product->name;
        $this->adjustCurrentStock = (string) $product->current_stock;
        $this->adjustNewStock = '';
        $this->adjustReason = '';
        $this->showAdjustModal = true;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjustNewStock' => 'required|numeric|min:0',
            'adjustReason' => 'required|string|max:500',
        ], [
            'adjustNewStock.required' => 'Ingrese el nuevo stock.',
            'adjustReason.required' => 'Ingrese el motivo del ajuste.',
        ]);

        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->adjustProductId);

        InventoryMovement::recordAdjustment(
            $product->id,
            (float) $this->adjustNewStock,
            $this->adjustReason
        );

        $this->showAdjustModal = false;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Stock ajustado correctamente.',
        ]);
    }

    public function openPurchaseModal(int $productId): void
    {
        $product = Product::where('tenant_id', auth()->user()->tenant_id)->findOrFail($productId);
        $this->purchaseProductId = $product->id;
        $this->purchaseProductName = $product->name;
        $this->purchaseQuantity = '';
        $this->purchaseUnitCost = '';
        $this->purchaseBatchNumber = '';
        $this->purchaseExpiryDate = '';
        $this->purchaseNotes = '';
        $this->showPurchaseModal = true;
    }

    public function savePurchase(): void
    {
        $this->validate([
            'purchaseQuantity' => 'required|numeric|min:0.01',
            'purchaseUnitCost' => 'required|numeric|min:0',
        ], [
            'purchaseQuantity.required' => 'Ingrese la cantidad.',
            'purchaseUnitCost.required' => 'Ingrese el costo unitario.',
        ]);

        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->purchaseProductId);

        InventoryMovement::recordPurchase(
            $product->id,
            (float) $this->purchaseQuantity,
            (float) $this->purchaseUnitCost,
            $this->purchaseBatchNumber ?: null,
            $this->purchaseExpiryDate ?: null,
            $this->purchaseNotes ?: null
        );

        $this->showPurchaseModal = false;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Compra registrada correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.inventory.inventory-dashboard', [
            'summary' => $this->summary,
            'lowStockProducts' => $this->lowStockProducts,
            'recentMovements' => $this->recentMovements,
        ])->layout('layouts.tenant', ['title' => 'Inventario']);
    }
}
