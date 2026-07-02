<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Documentos Recibidos
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Registro de facturas y comprobantes recibidos de proveedores y terceros
            </p>
        </div>
        <a href="{{ route('panel.received-documents.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Registrar documento
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total documentos</p>
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
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-amber-500 to-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['unprocessed'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Sin procesar</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Buscar por RUC, nombre o autorización..."
                    class="input w-full" />
            </div>
            <select wire:model.live="category" class="input w-full sm:w-48">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="isProcessed" class="input w-full sm:w-40">
                <option value="">Todos</option>
                <option value="1">Procesados</option>
                <option value="0">Sin procesar</option>
            </select>
            @if($search || $category || $isProcessed !== '')
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
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">Emisor</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('issue_date')">Fecha</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('total')">Total</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium">Procesado</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <span class="badge badge-blue text-xs">{{ $doc->documentTypeLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $doc->issuer_name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $doc->issuer_ruc }}</div>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $doc->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums">${{ number_format($doc->total, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($doc->expense_category)
                                    <span class="badge badge-{{ $doc->expense_category->color() }}">
                                        {{ $doc->expense_category->label() }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Sin categoría</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="markAsProcessed({{ $doc->id }})" title="Toggle procesado">
                                    @if($doc->is_processed)
                                        <span class="badge badge-green">Sí</span>
                                    @else
                                        <span class="badge badge-gray">No</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('panel.received-documents.edit', $doc) }}" class="btn-icon-sm" title="Editar">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                No hay documentos recibidos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
