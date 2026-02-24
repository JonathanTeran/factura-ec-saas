<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\ProductResource;
use App\Models\Tenant\Product;
use App\Models\Tenant\InventoryMovement;
use App\Enums\MovementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'movement_type' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $query = InventoryMovement::where('tenant_id', $request->user()->tenant_id)
            ->with(['product', 'createdBy'])
            ->orderByDesc('created_at');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $movements = $query->paginate($request->input('per_page', 20));

        return $this->paginated($movements, InventoryMovementResource::class);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $products = Product::where('tenant_id', $request->user()->tenant_id)
            ->lowStock()
            ->with('category')
            ->orderBy('current_stock')
            ->get();

        return $this->success([
            'products' => ProductResource::collection($products),
            'count' => $products->count(),
        ]);
    }

    public function adjust(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        if (!$product->track_inventory) {
            return $this->error('Este producto no tiene control de inventario activo.', 400);
        }

        $request->validate([
            'new_stock' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $movement = InventoryMovement::recordAdjustment(
            $product->id,
            $request->new_stock,
            $request->reason
        );

        return $this->success([
            'movement' => new InventoryMovementResource($movement->load(['product', 'createdBy'])),
            'product' => new ProductResource($product->fresh()),
        ], 'Inventario ajustado exitosamente');
    }

    public function purchase(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        if (!$product->track_inventory) {
            return $this->error('Este producto no tiene control de inventario activo.', 400);
        }

        $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'required|numeric|min:0',
            'batch_number' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $movement = InventoryMovement::recordPurchase(
            $product->id,
            $request->quantity,
            $request->unit_cost,
            $request->batch_number,
            $request->expiry_date,
            $request->notes
        );

        return $this->success([
            'movement' => new InventoryMovementResource($movement->load(['product', 'createdBy'])),
            'product' => new ProductResource($product->fresh()),
        ], 'Compra registrada exitosamente');
    }

    public function productMovements(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $movements = $product->inventoryMovements()
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($movements, InventoryMovementResource::class);
    }

    public function summary(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $totalProducts = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)
            ->count();

        $lowStockCount = Product::where('tenant_id', $tenantId)
            ->lowStock()
            ->count();

        $outOfStockCount = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)
            ->where('current_stock', '<=', 0)
            ->count();

        $totalValue = Product::where('tenant_id', $tenantId)
            ->where('track_inventory', true)
            ->selectRaw('SUM(current_stock * COALESCE(cost_price, unit_price)) as total')
            ->value('total') ?? 0;

        $recentMovements = InventoryMovement::where('tenant_id', $tenantId)
            ->with(['product', 'createdBy'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return $this->success([
            'summary' => [
                'total_products_with_inventory' => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'total_inventory_value' => round($totalValue, 2),
            ],
            'recent_movements' => InventoryMovementResource::collection($recentMovements),
        ]);
    }

    protected function authorizeProduct(Request $request, Product $product): void
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este producto.');
        }
    }
}
