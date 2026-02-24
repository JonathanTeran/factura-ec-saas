<?php

namespace App\Livewire\Panel\Categories;

use App\Models\Tenant\Category;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $parentFilter = '';
    public string $sortField = 'sort_order';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'parentFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'parentFilter']);
        $this->resetPage();
    }

    public function getCategoriesProperty()
    {
        return Category::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('products')
            ->with('parent')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->parentFilter !== '', fn($q) =>
                $this->parentFilter === 'root'
                    ? $q->whereNull('parent_id')
                    : $q->where('parent_id', $this->parentFilter)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total' => Category::where('tenant_id', $tenantId)->count(),
            'root' => Category::where('tenant_id', $tenantId)->whereNull('parent_id')->count(),
            'active' => Category::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'with_products' => Category::where('tenant_id', $tenantId)->has('products')->count(),
        ];
    }

    public function getParentCategoriesProperty()
    {
        return Category::where('tenant_id', auth()->user()->tenant_id)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function toggleActive(int $categoryId): void
    {
        $category = Category::where('tenant_id', auth()->user()->tenant_id)->findOrFail($categoryId);
        $category->update(['is_active' => !$category->is_active]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $category->is_active ? 'Categoría activada.' : 'Categoría desactivada.',
        ]);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::where('tenant_id', auth()->user()->tenant_id)->findOrFail($categoryId);

        if ($category->products()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar una categoría con productos asociados.',
            ]);
            return;
        }

        if ($category->children()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se puede eliminar una categoría con subcategorías.',
            ]);
            return;
        }

        $category->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.panel.categories.category-list', [
            'categories' => $this->categories,
            'stats' => $this->stats,
            'parentCategories' => $this->parentCategories,
        ])->layout('layouts.tenant', ['title' => 'Categorías']);
    }
}
