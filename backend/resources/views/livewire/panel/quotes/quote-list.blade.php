<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Cotizaciones
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Gestiona proformas y cotizaciones para tus clientes
            </p>
        </div>
        <a href="{{ route('panel.quotes.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva cotización
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['this_month'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Este mes</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-blue-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-blue-500 to-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">${{ number_format($stats['total_amount_month'], 2) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Monto mes</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-green-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-green-500 to-green-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['accepted'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Aceptadas</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Buscar por número o cliente..."
                    class="input w-full" />
            </div>
            <select wire:model.live="status" class="input w-full sm:w-48">
                <option value="">Todos los estados</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            @if($search || $status)
                <button wire:click="clearFilters" class="btn-ghost text-sm">Limpiar</button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('quote_number')">Número</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('issue_date')">Emisión</th>
                        <th class="px-4 py-3 font-medium">Vencimiento</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('total')">Total</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($quotes as $quote)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $quote->quote_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $quote->customer->name }}</div>
                                <div class="text-xs text-slate-500">{{ $quote->customer->identification }}</div>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $quote->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                @if($quote->expiry_date)
                                    <span class="{{ $quote->isExpired() ? 'text-red-500' : '' }}">
                                        {{ $quote->expiry_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums">${{ number_format($quote->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $quote->status->color() }}">
                                    {{ $quote->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($quote->isDraft())
                                        <a href="{{ route('panel.quotes.edit', $quote) }}" class="btn-icon-sm" title="Editar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </a>
                                        <button wire:click="deleteQuote({{ $quote->id }})"
                                            wire:confirm="¿Eliminar esta cotización?"
                                            class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    @else
                                        <a href="{{ route('panel.quotes.edit', $quote) }}" class="btn-icon-sm" title="Ver">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                No hay cotizaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotes->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $quotes->links() }}
            </div>
        @endif
    </div>
</div>
