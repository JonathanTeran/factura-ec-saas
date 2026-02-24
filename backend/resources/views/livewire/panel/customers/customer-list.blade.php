<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Clientes
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Gestiona tu base de clientes
            </p>
        </div>
        <a href="{{ route('panel.customers.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo cliente
        </a>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 stagger-children">
        {{-- Total --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600 shadow-lg shadow-primary-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['total'] }}">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total clientes</p>
                </div>
            </div>
        </div>

        {{-- New this month --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['thisMonth'] }}">{{ $stats['thisMonth'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Nuevos este mes</p>
                </div>
            </div>
        </div>

        {{-- With documents --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-violet-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-violet-500 to-violet-600 shadow-lg shadow-violet-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['withDocuments'] }}">{{ $stats['withDocuments'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Con documentos</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
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
                               placeholder="Buscar por nombre, RUC, cédula o correo..."
                               class="form-input !pl-11">
                    </div>
                </div>

                {{-- Type filter --}}
                <div class="w-full sm:w-48">
                    <label for="type" class="form-label">Tipo de identificación</label>
                    <select wire:model.live="type" id="type" class="form-input">
                        <option value="">Todos</option>
                        <option value="04">RUC</option>
                        <option value="05">Cédula</option>
                        <option value="06">Pasaporte</option>
                        <option value="07">Consumidor Final</option>
                    </select>
                </div>

                {{-- Clear filters --}}
                @if($search || $type)
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

    {{-- Customers Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="px-5">
                            <button wire:click="sortBy('business_name')" class="group inline-flex items-center gap-1.5">
                                Cliente
                                @if($sortField === 'business_name')
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
                        <th class="px-5">Contacto</th>
                        <th class="px-5 text-center">Documentos</th>
                        <th class="px-5 text-right">Total facturado</th>
                        <th class="px-5">
                            <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1.5">
                                Creado
                                @if($sortField === 'created_at')
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
                        <th class="relative px-5">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr class="group" wire:key="customer-{{ $customer->id }}">
                            <td class="px-5">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 dark:from-slate-700 dark:to-slate-600 dark:text-slate-300">
                                        <span class="text-sm font-semibold">{{ strtoupper(substr($customer->business_name, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $customer->business_name }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="font-mono text-xs text-slate-400 dark:text-slate-500 tabular-nums">
                                                {{ $customer->identification }}
                                            </span>
                                            @php
                                                $typeBadge = match($customer->identification_type) {
                                                    '04' => ['label' => 'RUC', 'class' => 'badge-primary'],
                                                    '05' => ['label' => 'CED', 'class' => 'badge-success'],
                                                    '06' => ['label' => 'PAS', 'class' => 'badge-warning'],
                                                    default => ['label' => 'CF', 'class' => 'badge-gray'],
                                                };
                                            @endphp
                                            <span class="inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset
                                                {{ $typeBadge['class'] === 'badge-primary' ? 'bg-primary-50 text-primary-700 ring-primary-200 dark:bg-primary-950/50 dark:text-primary-300 dark:ring-primary-800' : '' }}
                                                {{ $typeBadge['class'] === 'badge-success' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800' : '' }}
                                                {{ $typeBadge['class'] === 'badge-warning' ? 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-800' : '' }}
                                                {{ $typeBadge['class'] === 'badge-gray' ? 'bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700' : '' }}
                                            ">
                                                {{ $typeBadge['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5">
                                <div>
                                    @if($customer->email)
                                        <p class="text-sm text-slate-700 dark:text-slate-300">{{ $customer->email }}</p>
                                    @endif
                                    @if($customer->phone)
                                        <p class="text-sm text-slate-400 dark:text-slate-500">{{ $customer->phone }}</p>
                                    @endif
                                    @if(!$customer->email && !$customer->phone)
                                        <p class="text-sm italic text-slate-300 dark:text-slate-600">Sin contacto</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 text-center">
                                <span class="badge-gray tabular-nums">
                                    {{ $customer->documents_count }}
                                </span>
                            </td>
                            <td class="px-5 text-right">
                                <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                                    ${{ number_format($customer->documents_sum_total ?? 0, 2) }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-5">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ $customer->created_at->format('d/m/Y') }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100" x-data="{ open: false }">
                                    <a href="{{ route('panel.customers.edit', $customer) }}"
                                       class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                       title="Editar">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>

                                    <div class="relative">
                                        <button @click="open = !open" type="button"
                                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.away="open = false" x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="dropdown-menu absolute right-0 z-10 mt-2 w-48 origin-top-right p-1.5">
                                            <a href="{{ route('panel.documents.create') }}?customer={{ $customer->id }}" class="dropdown-item">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                                Nueva factura
                                            </a>
                                            <a href="{{ route('panel.documents.index') }}?customer={{ $customer->id }}" class="dropdown-item">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                                Ver documentos
                                            </a>
                                            <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>
                                            <button wire:click="deleteCustomer({{ $customer->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar este cliente?"
                                                    @click="open = false"
                                                    class="dropdown-item-danger w-full">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-4">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                        No hay clientes
                                    </h3>
                                    <p class="mt-1.5 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                                        @if($search || $type)
                                            No se encontraron clientes con los filtros aplicados.
                                        @else
                                            Comienza agregando tu primer cliente.
                                        @endif
                                    </p>
                                    @if(!$search && !$type)
                                        <a href="{{ route('panel.customers.create') }}" class="btn-primary mt-5">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Agregar cliente
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
        @if($customers->hasPages())
            <div class="card-footer">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
