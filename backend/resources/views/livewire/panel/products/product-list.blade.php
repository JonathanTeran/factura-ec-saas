<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Productos y Servicios
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Gestiona tu catálogo de productos y servicios
            </p>
        </div>
        <a href="{{ route('panel.products.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo producto
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['total'] }}">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total</p>
                </div>
            </div>
        </div>

        {{-- Products --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['products'] }}">{{ $stats['products'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Productos</p>
                </div>
            </div>
        </div>

        {{-- Services --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-violet-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-violet-500 to-violet-600 shadow-lg shadow-violet-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['services'] }}">{{ $stats['services'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Servicios</p>
                </div>
            </div>
        </div>

        {{-- Low stock --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['lowStock'] }}">{{ $stats['lowStock'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Stock bajo</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="form-label">Buscar</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                               placeholder="Buscar por nombre, código o código de barras..."
                               class="form-input !pl-11">
                    </div>
                </div>

                <div class="w-full sm:w-40">
                    <label class="form-label">Tipo</label>
                    <select wire:model.live="type" class="form-input">
                        <option value="">Todos</option>
                        <option value="product">Productos</option>
                        <option value="service">Servicios</option>
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <label class="form-label">Categoría</label>
                    <select wire:model.live="category" class="form-input">
                        <option value="">Todas</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-40">
                    <label class="form-label">Stock</label>
                    <select wire:model.live="stockStatus" class="form-input">
                        <option value="">Todos</option>
                        <option value="available">Disponible</option>
                        <option value="low">Stock bajo</option>
                        <option value="out">Sin stock</option>
                    </select>
                </div>

                @if($search || $type || $category || $stockStatus)
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

    {{-- Products Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="py-3.5 pl-5 pr-3">
                            <button wire:click="sortBy('main_code')" class="group inline-flex items-center gap-1.5">
                                Código
                                @if($sortField === 'main_code')
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
                        <th class="px-3 py-3.5">
                            <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1.5">
                                Producto
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
                        <th class="px-3 py-3.5">Tipo</th>
                        <th class="px-3 py-3.5 text-right">
                            <button wire:click="sortBy('unit_price')" class="group inline-flex items-center gap-1.5">
                                Precio
                                @if($sortField === 'unit_price')
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
                        <th class="px-3 py-3.5 text-center">Stock</th>
                        <th class="px-3 py-3.5 text-center">Estado</th>
                        <th class="relative py-3.5 pl-3 pr-5">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="group">
                            <td class="whitespace-nowrap py-3.5 pl-5 pr-3">
                                <span class="font-mono text-sm tabular-nums text-slate-500 dark:text-slate-400">{{ $product->main_code }}</span>
                            </td>
                            <td class="py-3.5 pl-3 pr-3">
                                <div class="flex items-center gap-3">
                                    @if($product->is_favorite)
                                        <button wire:click="toggleFavorite({{ $product->id }})"
                                                class="shrink-0 text-amber-500 transition-transform hover:scale-110">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                            </svg>
                                        </button>
                                    @else
                                        <button wire:click="toggleFavorite({{ $product->id }})"
                                                class="shrink-0 text-slate-300 transition-all hover:scale-110 hover:text-amber-500 dark:text-slate-600">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                            </svg>
                                        </button>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $product->name }}</p>
                                        @if($product->category)
                                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ $product->category->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                @if($product->type === 'product')
                                    <span class="badge-primary">
                                        <span class="badge-dot bg-primary-500"></span>
                                        Producto
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950/50 dark:text-violet-300 dark:ring-violet-800">
                                        <span class="badge-dot bg-violet-500"></span>
                                        Servicio
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-right">
                                <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">${{ number_format($product->unit_price, 2) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                @if($product->track_inventory)
                                    @if($product->current_stock <= 0)
                                        <span class="badge-danger">
                                            <span class="badge-dot bg-rose-500"></span>
                                            Sin stock
                                        </span>
                                    @elseif($product->isLowStock())
                                        <span class="badge-warning tabular-nums">
                                            <span class="badge-dot bg-amber-500 status-pulse"></span>
                                            {{ number_format($product->current_stock, 0) }}
                                        </span>
                                    @else
                                        <span class="text-sm tabular-nums text-slate-600 dark:text-slate-400">{{ number_format($product->current_stock, 0) }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">N/A</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <button wire:click="toggleActive({{ $product->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset transition-colors
                                        {{ $product->is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800'
                                            : 'bg-slate-50 text-slate-500 ring-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="relative whitespace-nowrap py-3.5 pl-3 pr-5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                    <a href="{{ route('panel.products.edit', $product) }}"
                                       class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                       title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    <button wire:click="deleteProduct({{ $product->id }})"
                                            wire:confirm="¿Estás seguro de eliminar este producto?"
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
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No hay productos</h3>
                                    <p class="mt-1.5 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                                        {{ $search || $type || $category ? 'No se encontraron productos con esos filtros.' : 'Comienza agregando tu primer producto.' }}
                                    </p>
                                    @if(!$search && !$type && !$category)
                                        <a href="{{ route('panel.products.create') }}" class="btn-primary mt-5">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Crear producto
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
        @if($products->hasPages())
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
