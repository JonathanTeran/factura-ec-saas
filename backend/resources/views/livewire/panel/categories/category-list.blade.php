<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Categorías
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Organiza tus productos en categorías
            </p>
        </div>
        <a href="{{ route('panel.categories.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva categoría
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 stagger-children">
        {{-- Total --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600 shadow-lg shadow-primary-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['total'] }}">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total</p>
                </div>
            </div>
        </div>

        {{-- Root --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-violet-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-violet-500 to-violet-600 shadow-lg shadow-violet-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['root'] }}">{{ $stats['root'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Raíz</p>
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['active'] }}">{{ $stats['active'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Activas</p>
                </div>
            </div>
        </div>

        {{-- With products --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['with_products'] }}">{{ $stats['with_products'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Con productos</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                {{-- Search --}}
                <div class="flex-1">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" id="search"
                               placeholder="Buscar por nombre..."
                               class="form-input !pl-11">
                    </div>
                </div>

                {{-- Parent filter --}}
                <div class="w-full sm:w-48">
                    <label for="parentFilter" class="form-label">Categoría padre</label>
                    <select wire:model.live="parentFilter" id="parentFilter" class="form-input">
                        <option value="">Todas</option>
                        <option value="root">Solo raíz</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear filters --}}
                @if($search || $parentFilter)
                    <button wire:click="clearFilters" type="button" class="btn-ghost btn-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Categories Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="py-3.5 pl-5 pr-3 w-12">Color</th>
                        <th class="px-3 py-3.5">
                            <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1.5">
                                Nombre
                                @if($sortField === 'name')
                                    <svg class="h-3.5 w-3.5 text-primary-500 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3.5">Categoría padre</th>
                        <th class="px-3 py-3.5 text-center">Productos</th>
                        <th class="px-3 py-3.5 text-center">
                            <button wire:click="sortBy('sort_order')" class="group inline-flex items-center gap-1.5">
                                Orden
                                @if($sortField === 'sort_order')
                                    <svg class="h-3.5 w-3.5 text-primary-500 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3.5 text-center">Estado</th>
                        <th class="relative py-3.5 pl-3 pr-5">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr class="group" wire:key="category-{{ $category->id }}">
                            <td class="whitespace-nowrap py-3.5 pl-5 pr-3">
                                <span class="inline-block h-6 w-6 rounded-full ring-2 ring-white shadow-sm dark:ring-slate-800"
                                      style="background-color: {{ $category->color ?? '#3b82f6' }}"></span>
                            </td>
                            <td class="py-3.5 pl-3 pr-3">
                                <div class="flex items-center gap-3">
                                    @if($category->icon)
                                        <span class="text-lg text-slate-500 dark:text-slate-400">{{ $category->icon }}</span>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $category->name }}</p>
                                        @if($category->description)
                                            <p class="text-xs text-slate-400 dark:text-slate-500 truncate max-w-xs">{{ $category->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                @if($category->parent)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $category->parent->color ?? '#94a3b8' }}"></span>
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">Raíz</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <span class="badge-gray tabular-nums">
                                    {{ $category->products_count }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <span class="text-sm tabular-nums text-slate-600 dark:text-slate-400">{{ $category->sort_order }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <button wire:click="toggleActive({{ $category->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset transition-colors
                                        {{ $category->is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800'
                                            : 'bg-slate-50 text-slate-500 ring-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                </button>
                            </td>
                            <td class="relative whitespace-nowrap py-3.5 pl-3 pr-5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                    <a href="{{ route('panel.categories.edit', $category) }}"
                                       class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                       title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    <button wire:click="deleteCategory({{ $category->id }})"
                                            wire:confirm="¿Estás seguro de eliminar esta categoría?"
                                            class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400"
                                            title="Eliminar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                        No hay categorías
                                    </h3>
                                    <p class="mt-1.5 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                                        @if($search || $parentFilter)
                                            No se encontraron categorías con los filtros aplicados.
                                        @else
                                            Comienza agregando tu primera categoría.
                                        @endif
                                    </p>
                                    @if(!$search && !$parentFilter)
                                        <a href="{{ route('panel.categories.create') }}" class="btn-primary mt-5">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Crear categoría
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($categories->hasPages())
            <div class="card-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
