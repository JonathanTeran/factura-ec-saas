<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Historial de Actividad</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Registro de acciones realizadas en tu cuenta.
            </p>
        </div>
        <a href="{{ route('panel.settings.index') }}" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
            &larr; Configuración
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <select wire:model.live="event" class="input w-full sm:w-48">
                <option value="">Todos los eventos</option>
                @foreach($events as $evt)
                    <option value="{{ $evt }}">{{ $evt }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="dateFrom" class="input w-full sm:w-40" placeholder="Desde" />
            <input type="date" wire:model.live="dateTo" class="input w-full sm:w-40" placeholder="Hasta" />
            @if($event || $dateFrom || $dateTo)
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
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 font-medium">Evento</th>
                        <th class="px-4 py-3 font-medium">Descripción</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-xs text-slate-500 tabular-nums whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $log->user?->name ?? 'Sistema' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                    {{ $log->event }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                No hay actividad registrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
