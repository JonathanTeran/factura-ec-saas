<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-4">
            <a href="{{ route('panel.inventory.index') }}" class="btn-ghost btn-icon shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    Movimientos de inventario
                </h1>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                    Historial completo de entradas y salidas de stock
                </p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                {{-- Search --}}
                <div class="flex-1">
                    <label class="form-label">Buscar</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                               placeholder="Buscar por producto o codigo..."
                               class="form-input !pl-11">
                    </div>
                </div>

                {{-- Movement type --}}
                <div class="w-full sm:w-48">
                    <label class="form-label">Tipo de movimiento</label>
                    <select wire:model.live="movementType" class="form-input">
                        <option value="">Todos</option>
                        @foreach($movementTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Product --}}
                <div class="w-full sm:w-48">
                    <label class="form-label">Producto</label>
                    <select wire:model.live="productFilter" class="form-input">
                        <option value="">Todos</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date from --}}
                <div class="w-full sm:w-40">
                    <label class="form-label">Desde</label>
                    <input wire:model.live="dateFrom" type="date" class="form-input">
                </div>

                {{-- Date to --}}
                <div class="w-full sm:w-40">
                    <label class="form-label">Hasta</label>
                    <input wire:model.live="dateTo" type="date" class="form-input">
                </div>

                {{-- Clear filters --}}
                @if($search || $movementType || $productFilter || $dateFrom || $dateTo)
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

    {{-- Movements Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="py-3.5 pl-5 pr-3">
                            <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1.5">
                                Fecha
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
                        <th class="px-3 py-3.5">Producto</th>
                        <th class="px-3 py-3.5">Tipo</th>
                        <th class="px-3 py-3.5 text-right">
                            <button wire:click="sortBy('quantity')" class="group inline-flex items-center gap-1.5">
                                Cantidad
                                @if($sortField === 'quantity')
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
                        <th class="px-3 py-3.5 text-right">Stock antes</th>
                        <th class="px-3 py-3.5 text-right">Stock despues</th>
                        <th class="px-3 py-3.5 text-right">Costo</th>
                        <th class="px-3 py-3.5">Usuario</th>
                        <th class="px-3 py-3.5 pr-5">Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        @php
                            $color = $movement->movement_type->color();
                            $badgeClasses = match($color) {
                                'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800',
                                'red' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-800',
                                'yellow' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-800',
                                'blue' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-800',
                                'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:ring-indigo-800',
                                'orange' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:ring-orange-800',
                                'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-950/50 dark:text-cyan-300 dark:ring-cyan-800',
                                'purple' => 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:ring-purple-800',
                                'teal' => 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-950/50 dark:text-teal-300 dark:ring-teal-800',
                                default => 'bg-slate-50 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
                            };
                            $dotColor = match($color) {
                                'green' => 'bg-emerald-500',
                                'red' => 'bg-rose-500',
                                'yellow' => 'bg-amber-500',
                                'blue' => 'bg-blue-500',
                                'indigo' => 'bg-indigo-500',
                                'orange' => 'bg-orange-500',
                                'cyan' => 'bg-cyan-500',
                                'purple' => 'bg-purple-500',
                                'teal' => 'bg-teal-500',
                                default => 'bg-slate-400',
                            };
                        @endphp
                        <tr class="group" wire:key="movement-row-{{ $movement->id }}">
                            <td class="whitespace-nowrap py-3.5 pl-5 pr-3">
                                <div>
                                    <p class="text-sm text-slate-900 dark:text-white tabular-nums">{{ $movement->created_at->format('d/m/Y') }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 tabular-nums mt-0.5">{{ $movement->created_at->format('H:i') }}</p>
                                </div>
                            </td>
                            <td class="py-3.5 px-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $movement->product?->name ?? 'Producto eliminado' }}</p>
                                    <p class="text-xs font-mono text-slate-400 dark:text-slate-500 tabular-nums mt-0.5">{{ $movement->product?->main_code ?? '-' }}</p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
                                    {{ $movement->movement_type->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-right">
                                <span class="text-sm font-semibold tabular-nums {{ $movement->movement_type->isIncoming() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $movement->movement_type->isIncoming() ? '+' : '' }}{{ number_format($movement->quantity, 0) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-right">
                                <span class="text-sm tabular-nums text-slate-600 dark:text-slate-400">{{ number_format($movement->stock_before, 0) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-right">
                                <span class="text-sm font-medium tabular-nums text-slate-900 dark:text-white">{{ number_format($movement->stock_after, 0) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-right">
                                @if($movement->total_cost)
                                    <span class="text-sm tabular-nums text-slate-600 dark:text-slate-400">${{ number_format($movement->total_cost, 2) }}</span>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ $movement->createdBy?->name ?? 'Sistema' }}</span>
                            </td>
                            <td class="px-3 py-3.5 pr-5">
                                @if($movement->notes)
                                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-[200px] truncate" title="{{ $movement->notes }}">
                                        {{ $movement->notes }}
                                    </p>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-4">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No hay movimientos</h3>
                                    <p class="mt-1.5 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                                        @if($search || $movementType || $productFilter || $dateFrom || $dateTo)
                                            No se encontraron movimientos con los filtros aplicados.
                                        @else
                                            Los movimientos de inventario apareceran aqui cuando se registren compras, ventas o ajustes.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($movements->hasPages())
            <div class="card-footer">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
