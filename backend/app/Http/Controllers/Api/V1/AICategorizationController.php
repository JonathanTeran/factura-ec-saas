<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tenant\Product;
use App\Services\AI\AICategorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AICategorizationController extends ApiController
{
    public function __construct(
        private readonly AICategorizationService $aiService,
    ) {}

    /**
     * Categorizar un producto individualmente.
     */
    public function categorize(Request $request, Product $product): JsonResponse
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        try {
            $category = $this->aiService
                ->forTenant($request->user()->tenant)
                ->categorizeProduct($product);

            if ($category) {
                return $this->success([
                    'product_id' => $product->id,
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                ], 'Producto categorizado exitosamente');
            }

            return $this->error('No se pudo determinar una categoria apropiada.', 422);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Categorizar multiples productos en lote.
     */
    public function categorizeBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:50'],
            'product_ids.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        try {
            $results = $this->aiService
                ->forTenant($request->user()->tenant)
                ->categorizeProducts($validated['product_ids']);

            $categorized = collect($results)->where('status', 'categorized')->count();

            return $this->success([
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'categorized' => $categorized,
                    'failed' => count($results) - $categorized,
                ],
            ], "{$categorized} productos categorizados exitosamente");
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Sugerir nombre de categoria para un producto.
     */
    public function suggest(Request $request, Product $product): JsonResponse
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        try {
            $suggestion = $this->aiService
                ->forTenant($request->user()->tenant)
                ->suggestCategory($product);

            if ($suggestion) {
                return $this->success([
                    'product_id' => $product->id,
                    'suggested_category' => $suggestion,
                ], 'Sugerencia generada exitosamente');
            }

            return $this->error('No se pudo generar una sugerencia.', 422);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
