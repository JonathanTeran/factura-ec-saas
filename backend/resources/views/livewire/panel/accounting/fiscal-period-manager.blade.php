<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Periodos Fiscales
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Gestion de periodos mensuales y anuales
            </p>
        </div>
        <div class="flex gap-2">
            <button wire:click="generateOpeningEntry"
                    wire:confirm="Generar asiento de apertura para {{ $selectedYear }}?"
                    class="btn-secondary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Asiento de apertura
            </button>
            <button wire:click="generateClosingEntry"
                    wire:confirm="Generar asiento de cierre para {{ $selectedYear }}? Se creara un asiento en borrador."
                    class="btn-secondary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Asiento de cierre
            </button>
        </div>
    </div>

    {{-- Year Selector --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-40">
                <label class="form-label">Ano fiscal</label>
                <select wire:model.live="selectedYear" class="input w-full">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            @if($periods->isEmpty())
                <button wire:click="createPeriods" class="btn-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Crear periodos para {{ $selectedYear }}
                </button>
            @endif
        </div>
    </div>

    {{-- Periods List --}}
    @if($periods->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">Periodo</th>
                            <th class="px-4 py-3 font-medium">Tipo</th>
                            <th class="px-4 py-3 font-medium">Fecha inicio</th>
                            <th class="px-4 py-3 font-medium">Fecha fin</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 font-medium">Cerrado por</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($periods as $period)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 {{ $period->period_type === 'annual' ? 'bg-slate-25 dark:bg-slate-800/25 font-medium' : '' }}">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-slate-900 dark:text-white">
                                        {{ $period->getLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($period->period_type === 'annual')
                                        <span class="badge badge-blue">Anual</span>
                                    @else
                                        <span class="text-sm text-slate-500 dark:text-slate-400">Mensual</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 tabular-nums text-slate-600 dark:text-slate-400">
                                    {{ $period->start_date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 tabular-nums text-slate-600 dark:text-slate-400">
                                    {{ $period->end_date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge badge-{{ $period->status->color() }}">
                                        {{ $period->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                    @if($period->closed_at)
                                        <div>
                                            {{ $period->closedByUser?->name ?? '-' }}
                                            <p class="text-xs text-slate-400">{{ $period->closed_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if($period->status->value === 'open')
                                            <button wire:click="closePeriod({{ $period->id }})"
                                                    wire:confirm="Cerrar el periodo {{ $period->getLabel() }}? Asegurese de que todos los asientos esten contabilizados."
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                                    title="Cerrar periodo">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                Cerrar
                                            </button>
                                        @endif
                                        @if($period->status->value === 'closed')
                                            <button wire:click="lockPeriod({{ $period->id }})"
                                                    wire:confirm="Bloquear el periodo {{ $period->getLabel() }}? Esta accion es permanente."
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                    title="Bloquear periodo">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                Bloquear
                                            </button>
                                            <button wire:click="reopenPeriod({{ $period->id }})"
                                                    wire:confirm="Reabrir el periodo {{ $period->getLabel() }}?"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                                                    title="Reabrir periodo">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                Reabrir
                                            </button>
                                        @endif
                                        @if($period->status->value === 'locked')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-slate-400 dark:text-slate-500">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                Bloqueado
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">No hay periodos para {{ $selectedYear }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Crea los 12 periodos mensuales y el periodo anual con un solo clic.
            </p>
            <button wire:click="createPeriods" class="btn-primary mt-5">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Crear periodos para {{ $selectedYear }}
            </button>
        </div>
    @endif
</div>
