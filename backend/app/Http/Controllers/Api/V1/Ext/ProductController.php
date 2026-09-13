<?php

namespace App\Http\Controllers\Api\V1\Ext;

use App\Http\Controllers\Api\V1\ProductController as PanelProductController;
use App\Models\Tenant\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Productos y servicios para integraciones externas (/api/v1/ext/products). */
class ProductController extends PanelProductController
{
    protected function authorizeProduct(Request $request, Product $product): void
    {
        if ((int) $product->tenant_id !== (int) $request->user()->tenant_id) {
            throw new NotFoundHttpException('Producto no encontrado.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $request->merge(['per_page' => DocumentController::clampPerPage($request->input('per_page'))]);

        return parent::index($request);
    }
}
