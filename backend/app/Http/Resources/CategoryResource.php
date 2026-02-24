<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_root' => $this->isRoot(),
            'full_path' => $this->getFullPath(),
            'product_count' => $this->whenCounted('products'),
            'parent' => $this->whenLoaded('parent', fn() => new CategoryResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => CategoryResource::collection($this->children)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
