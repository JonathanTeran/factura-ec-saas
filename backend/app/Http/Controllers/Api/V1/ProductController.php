<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Tenant\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Productos
 */
class ProductController extends ApiController
{
    /**
     * Listar productos
     *
     * Retorna los productos del tenant con búsqueda, filtros y paginación.
     *
     * @queryParam search string Búsqueda por nombre, código o SKU.
     * @queryParam type string Tipo de producto (product, service).
     * @queryParam active_only bool Solo productos activos. Default: true.
     * @queryParam per_page int Resultados por página. Default: 15.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('main_code', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        $products = $query->paginate($request->input('per_page', 15));

        return $this->paginated($products, ProductResource::class);
    }

    /**
     * Crear producto
     *
     * Registra un nuevo producto o servicio asociado al tenant actual.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Map API field names to model column names
        if (isset($data['code'])) {
            $data['main_code'] = $data['code'];
            unset($data['code']);
        }
        if (isset($data['cost'])) {
            $data['cost_price'] = $data['cost'];
            unset($data['cost']);
        }
        if (isset($data['stock'])) {
            $data['current_stock'] = $data['stock'];
            unset($data['stock']);
        }

        $product = Product::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$data,
        ]);

        return $this->created([
            'product' => new ProductResource($product),
        ], 'Producto creado exitosamente');
    }

    /**
     * Ver producto
     *
     * Retorna el detalle de un producto.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        return $this->success([
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Actualizar producto
     *
     * Actualiza los datos de un producto existente.
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $product->update($request->validated());

        return $this->success([
            'product' => new ProductResource($product),
        ], 'Producto actualizado exitosamente');
    }

    /**
     * Eliminar producto
     *
     * No se puede eliminar si está asociado a documentos.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        // Check if product has document items
        if ($product->documentItems()->exists()) {
            return $this->error(
                'No se puede eliminar el producto porque está asociado a documentos.',
                400
            );
        }

        $product->delete();

        return $this->success(null, 'Producto eliminado exitosamente');
    }

    /**
     * Buscar productos
     *
     * Búsqueda rápida (máximo 10 resultados) por nombre, código o SKU.
     */
    public function search(Request $request, string $query): JsonResponse
    {
        $products = Product::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return $this->success([
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * Ajustar stock
     *
     * Realiza un ajuste positivo o negativo al stock del producto.
     * Solo aplica a productos con control de inventario activo.
     */
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        if (!$product->track_inventory) {
            return $this->error('Este producto no tiene control de inventario activo.', 400);
        }

        $validated = $request->validate([
            'adjustment' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $newStock = $product->stock + $validated['adjustment'];

        if ($newStock < 0) {
            return $this->error('El stock no puede ser negativo.', 400);
        }

        $product->update(['stock' => $newStock]);

        // Log stock adjustment
        activity()
            ->performedOn($product)
            ->withProperties([
                'previous_stock' => $product->getOriginal('stock'),
                'adjustment' => $validated['adjustment'],
                'new_stock' => $newStock,
                'reason' => $validated['reason'] ?? null,
            ])
            ->log('stock_adjusted');

        return $this->success([
            'product' => new ProductResource($product->fresh()),
        ], 'Stock ajustado exitosamente');
    }

    protected function authorizeProduct(Request $request, Product $product): void
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a este producto.');
        }
    }
}
