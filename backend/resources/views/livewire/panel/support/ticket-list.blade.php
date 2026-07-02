<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Soporte
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Envía consultas y reporta problemas al equipo de soporte
            </p>
        </div>
        <a href="{{ route('panel.support.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo ticket
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total tickets</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-blue-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-blue-500 to-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['open'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Abiertos</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-yellow-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-yellow-500 to-yellow-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['in_progress'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">En proceso</p>
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
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['resolved'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Resueltos</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Buscar por asunto..."
                    class="input w-full" />
            </div>
            <select wire:model.live="status" class="input w-full sm:w-48">
                <option value="">Todos los estados</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="priority" class="input w-full sm:w-40">
                <option value="">Todas las prioridades</option>
                @foreach($priorities as $p)
                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                @endforeach
            </select>
            @if($search || $status || $priority)
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
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('subject')">Asunto</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium">Prioridad</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('created_at')">Fecha</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">#{{ $ticket->id }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('panel.support.show', $ticket) }}" class="font-medium hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $ticket->subject }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $ticket->category->color() }}">{{ $ticket->category->label() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $ticket->priority->color() }}">{{ $ticket->priority->label() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('panel.support.show', $ticket) }}" class="btn-icon-sm" title="Ver ticket">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                No tienes tickets de soporte.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
