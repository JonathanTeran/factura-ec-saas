<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Guías de Remisión
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Comprobantes de traslado de mercadería autorizados por el SRI
            </p>
        </div>
        <a href="{{ route('panel.guides.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva guía
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total guías</p>
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
            <div class="stat-card-glow bg-green-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-green-500 to-green-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['authorized'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Autorizadas</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-amber-500 to-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['pending'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pendientes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Buscar por número, destinatario o placa..."
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
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('sequential_number')">Número</th>
                        <th class="px-4 py-3 font-medium">Destinatario</th>
                        <th class="px-4 py-3 font-medium">Transportista</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('issue_date')">Fecha</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($guides as $guide)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold">
                                {{ $guide->sequential_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $guide->destination_name ?? '—' }}</div>
                                @if($guide->destination_ruc)
                                    <div class="text-xs text-slate-500 font-mono">{{ $guide->destination_ruc }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($guide->carrier_plate)
                                    <span class="font-mono">{{ $guide->carrier_plate }}</span>
                                    @if($guide->carrier_name)
                                        <div class="text-slate-500">{{ $guide->carrier_name }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $guide->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $guide->status->color() }}">
                                    {{ $guide->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('panel.documents.show', $guide) }}" class="btn-icon-sm" title="Ver documento">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                No hay guías de remisión registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($guides->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $guides->links() }}
            </div>
        @endif
    </div>
</div>
