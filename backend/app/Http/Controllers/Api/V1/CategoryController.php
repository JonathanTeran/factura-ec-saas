<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Tenant\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::where('tenant_id', $request->user()->tenant_id)
            ->with(['parent', 'children'])
            ->withCount('products');

        if ($request->boolean('roots_only')) {
            $query->roots();
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        $categories = $query->ordered()->get();

        return $this->success([
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        return $this->created([
            'category' => new CategoryResource($category),
        ], 'Categoría creada exitosamente');
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);

        $category->load(['parent', 'children.children', 'products']);
        $category->loadCount('products');

        return $this->success([
            'category' => new CategoryResource($category),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);

        $category->update($request->validated());

        return $this->success([
            'category' => new CategoryResource($category),
        ], 'Categoría actualizada exitosamente');
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);

        if ($category->products()->exists()) {
            return $this->error(
                'No se puede eliminar la categoría porque tiene productos asociados.',
                400
            );
        }

        if ($category->children()->exists()) {
            return $this->error(
                'No se puede eliminar la categoría porque tiene subcategorías.',
                400
            );
        }

        $category->delete();

        return $this->success(null, 'Categoría eliminada exitosamente');
    }

    public function tree(Request $request): JsonResponse
    {
        $categories = Category::where('tenant_id', $request->user()->tenant_id)
            ->roots()
            ->active()
            ->with(['children' => function ($q) {
                $q->active()->ordered()->with(['children' => function ($q2) {
                    $q2->active()->ordered();
                }]);
            }])
            ->ordered()
            ->get();

        return $this->success([
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    protected function authorizeCategory(Request $request, Category $category): void
    {
        if ($category->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'No tienes acceso a esta categoría.');
        }
    }
}
