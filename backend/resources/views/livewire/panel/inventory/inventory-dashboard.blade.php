<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Inventario
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Control y seguimiento de stock de productos
            </p>
        </div>
        <a href="{{ route('panel.inventory.movements') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all duration-200 shadow-sm">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            Ver movimientos
        </a>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 stagger-children">
        {{-- Productos con inventario --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Productos con inventario</p>
                    <p class="mt-2 stat-value tabular-nums">
                        {{ number_format($summary['tracked']) }}
                    </p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Stock bajo --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Stock bajo</p>
                    <p class="mt-2 stat-value tabular-nums">
                        {{ number_format($summary['low_stock']) }}
                    </p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-500/25 group-hover:shadow-amber-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Sin stock --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-rose-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Sin stock</p>
                    <p class="mt-2 stat-value tabular-nums">
                        {{ number_format($summary['out_of_stock']) }}
                    </p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-rose-500 to-rose-700 shadow-lg shadow-rose-500/25 group-hover:shadow-rose-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Valor total inventario --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Valor total inventario</p>
                    <p class="mt-2 stat-value tabular-nums">
                        ${{ number_format($summary['total_value'], 2) }}
                    </p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/25 group-hover:shadow-emerald-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main content grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Productos con stock bajo --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30">
                            <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Productos con stock bajo</h2>
                    </div>
                </div>

                @if($lowStockProducts->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Todo en orden</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-xs">No hay productos con stock bajo o agotado</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="py-3.5 pl-5 pr-3">Producto</th>
                                    <th class="px-3 py-3.5 text-center">Stock actual</th>
                                    <th class="px-3 py-3.5 text-center">Stock minimo</th>
                                    <th class="relative py-3.5 pl-3 pr-5">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $product)
                                    <tr class="group" wire:key="low-stock-{{ $product->id }}">
                                        <td class="py-3.5 pl-5 pr-3">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $product->name }}</p>
                                                <p class="text-xs font-mono text-slate-400 dark:text-slate-500 tabular-nums mt-0.5">{{ $product->main_code }}</p>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                            @if($product->current_stock <= 0)
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-800">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                    {{ number_format($product->current_stock, 0) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-800">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 status-pulse"></span>
                                                    {{ number_format($product->current_stock, 0) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                            <span class="text-sm tabular-nums text-slate-600 dark:text-slate-400">{{ number_format($product->min_stock, 0) }}</span>
                                        </td>
                                        <td class="relative whitespace-nowrap py-3.5 pl-3 pr-5 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button wire:click="openAdjustModal({{ $product->id }})"
                                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-primary-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                                        title="Ajustar stock">
                                                    <svg class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                                    </svg>
                                                    Ajustar
                                                </button>
                                                <button wire:click="openPurchaseModal({{ $product->id }})"
                                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:text-slate-400 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400"
                                                        title="Registrar compra">
                                                    <svg class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                                    </svg>
                                                    Compra
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Movimientos recientes --}}
        <div>
            <div class="card">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                            <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Movimientos recientes</h2>
                    </div>
                    <a href="{{ route('panel.inventory.movements') }}"
                       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 transition-colors">
                        Ver todos
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>

                @if($recentMovements->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Sin movimientos</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-xs">Los movimientos de inventario apareceran aqui</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($recentMovements as $movement)
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
                            @endphp
                            <div class="flex items-center gap-3 px-6 py-3.5" wire:key="movement-{{ $movement->id }}">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                        {{ $movement->product?->name ?? 'Producto eliminado' }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $badgeClasses }}">
                                            {{ $movement->movement_type->label() }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500">
                                            {{ $movement->createdBy?->name ?? 'Sistema' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold tabular-nums {{ $movement->movement_type->isIncoming() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $movement->movement_type->isIncoming() ? '+' : '' }}{{ number_format($movement->quantity, 0) }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        {{ $movement->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Adjustment Modal --}}
    <div x-data="{ show: @entangle('showAdjustModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Overlay --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="show = false"
                 class="fixed inset-0 bg-slate-500/75 dark:bg-slate-900/80 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:max-w-lg sm:align-middle ring-1 ring-slate-900/5 dark:ring-white/10">
                <form wire:submit="saveAdjustment">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-900/30">
                                <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white" id="modal-title">
                                    Ajustar stock
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $adjustProductName }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            {{-- Stock actual --}}
                            <div>
                                <label class="form-label">Stock actual</label>
                                <input type="text" value="{{ $adjustCurrentStock }}" disabled
                                       class="form-input bg-slate-50 dark:bg-slate-900/50 cursor-not-allowed tabular-nums">
                            </div>

                            {{-- Nuevo stock --}}
                            <div>
                                <label for="adjustNewStock" class="form-label">Nuevo stock</label>
                                <input wire:model="adjustNewStock" type="number" step="0.01" min="0" id="adjustNewStock"
                                       placeholder="Ingrese el nuevo stock"
                                       class="form-input tabular-nums">
                                @error('adjustNewStock')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Motivo --}}
                            <div>
                                <label for="adjustReason" class="form-label">Motivo del ajuste</label>
                                <textarea wire:model="adjustReason" id="adjustReason" rows="3"
                                          placeholder="Describa el motivo del ajuste..."
                                          class="form-input"></textarea>
                                @error('adjustReason')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700 px-6 py-4">
                        <button type="button" @click="show = false" class="btn-ghost">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Guardar ajuste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Purchase Modal --}}
    <div x-data="{ show: @entangle('showPurchaseModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="purchase-modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Overlay --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="show = false"
                 class="fixed inset-0 bg-slate-500/75 dark:bg-slate-900/80 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:max-w-lg sm:align-middle ring-1 ring-slate-900/5 dark:ring-white/10">
                <form wire:submit="savePurchase">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/30">
                                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white" id="purchase-modal-title">
                                    Registrar compra
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $purchaseProductName }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            {{-- Cantidad --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="purchaseQuantity" class="form-label">Cantidad</label>
                                    <input wire:model="purchaseQuantity" type="number" step="0.01" min="0.01" id="purchaseQuantity"
                                           placeholder="0.00"
                                           class="form-input tabular-nums">
                                    @error('purchaseQuantity')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Costo unitario --}}
                                <div>
                                    <label for="purchaseUnitCost" class="form-label">Costo unitario</label>
                                    <input wire:model="purchaseUnitCost" type="number" step="0.01" min="0" id="purchaseUnitCost"
                                           placeholder="$0.00"
                                           class="form-input tabular-nums">
                                    @error('purchaseUnitCost')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Numero de lote --}}
                            <div>
                                <label for="purchaseBatchNumber" class="form-label">Numero de lote <span class="text-slate-400 font-normal">(opcional)</span></label>
                                <input wire:model="purchaseBatchNumber" type="text" id="purchaseBatchNumber"
                                       placeholder="Ej: LOTE-2026-001"
                                       class="form-input">
                            </div>

                            {{-- Fecha de vencimiento --}}
                            <div>
                                <label for="purchaseExpiryDate" class="form-label">Fecha de vencimiento <span class="text-slate-400 font-normal">(opcional)</span></label>
                                <input wire:model="purchaseExpiryDate" type="date" id="purchaseExpiryDate"
                                       class="form-input">
                            </div>

                            {{-- Notas --}}
                            <div>
                                <label for="purchaseNotes" class="form-label">Notas <span class="text-slate-400 font-normal">(opcional)</span></label>
                                <textarea wire:model="purchaseNotes" id="purchaseNotes" rows="2"
                                          placeholder="Notas adicionales sobre la compra..."
                                          class="form-input"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700 px-6 py-4">
                        <button type="button" @click="show = false" class="btn-ghost">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Registrar compra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
