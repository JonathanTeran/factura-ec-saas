<?php

namespace App\Livewire\Panel\Categories;

use App\Models\Tenant\Category;
use Illuminate\Support\Str;
use Livewire\Component;

class CategoryForm extends Component
{
    public ?Category $category = null;

    public ?int $parent_id = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $color = '#3b82f6';
    public string $icon = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    public function mount(?Category $category = null): void
    {
        if ($category && $category->exists) {
            if ($category->tenant_id !== auth()->user()->tenant_id) {
                abort(403);
            }

            $this->category = $category;

            $this->parent_id = $this->category->parent_id;
            $this->name = $this->category->name;
            $this->slug = $this->category->slug;
            $this->description = $this->category->description ?? '';
            $this->color = $this->category->color ?? '#3b82f6';
            $this->icon = $this->category->icon ?? '';
            $this->sort_order = $this->category->sort_order;
            $this->is_active = $this->category->is_active;
        }
    }

    public function updatedName(): void
    {
        if (!$this->category) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $categoryId = $this->category?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($tenantId, $categoryId) {
                    $exists = Category::where('tenant_id', $tenantId)
                        ->where('name', $value)
                        ->when($categoryId, fn($q) => $q->where('id', '!=', $categoryId))
                        ->exists();
                    if ($exists) {
                        $fail('Ya existe una categoría con este nombre.');
                    }
                },
            ],
            'slug' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:20',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'name.required' => 'El nombre es requerido.',
        'slug.required' => 'El slug es requerido.',
        'sort_order.required' => 'El orden es requerido.',
    ];

    public function getParentCategoriesProperty()
    {
        $query = Category::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name');

        if ($this->category) {
            $query->where('id', '!=', $this->category->id)
                ->where(function ($q) {
                    $q->whereNull('parent_id')
                        ->orWhere('parent_id', '!=', $this->category->id);
                });
        }

        return $query->get();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'color' => $this->color,
            'icon' => $this->icon ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->category) {
            $this->category->update($data);
        } else {
            $data['tenant_id'] = auth()->user()->tenant_id;
            Category::create($data);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->category
                ? 'Categoría actualizada correctamente.'
                : 'Categoría creada correctamente.',
        ]);

        $this->redirect(route('panel.categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.panel.categories.category-form', [
            'parentCategories' => $this->parentCategories,
        ])->layout('layouts.tenant', [
            'title' => $this->category ? 'Editar Categoría' : 'Nueva Categoría',
        ]);
    }
}
