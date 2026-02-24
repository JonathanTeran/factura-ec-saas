<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('panel.accounting.budgets') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400" wire:navigate>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver a presupuestos
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Ejecucion Presupuestaria
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Comparativa entre lo presupuestado y lo ejecutado
            </p>
        </div>
    </div>

    {{-- Budget Selector --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="form-label">Presupuesto</label>
                <select wire:model.live="budgetId" class="input w-full">
                    <option value="">Seleccionar presupuesto...</option>
                    @foreach($budgets as $b)
                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->year }}) - {{ $b->status->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($selectedBudget)
        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="stat-card">
                <div class="stat-card-glow bg-blue-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">${{ number_format($summary['total_budgeted'], 2) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total presupuestado</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow bg-emerald-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">${{ number_format($summary['total_executed'], 2) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total ejecutado</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow {{ $summary['percentage'] > 100 ? 'bg-red-500' : ($summary['percentage'] > 80 ? 'bg-amber-500' : 'bg-primary-500') }}"></div>
                <div>
                    <div class="flex items-center gap-3">
                        <p class="stat-value tabular-nums text-2xl">{{ number_format($summary['percentage'], 1) }}%</p>
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div class="h-full rounded-full transition-all
                                {{ $summary['percentage'] > 100 ? 'bg-red-500' : ($summary['percentage'] > 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                style="width: {{ min($summary['percentage'], 100) }}%">
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ejecucion global</p>
                </div>
            </div>
        </div>

        {{-- Execution Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <th class="sticky left-0 z-10 bg-slate-50 px-3 py-2.5 font-medium dark:bg-slate-800">Cuenta</th>
                            @foreach($months as $num => $label)
                                <th class="px-2 py-2.5 text-center font-medium">{{ $label }}</th>
                            @endforeach
                            <th class="px-3 py-2.5 text-right font-medium">Presup.</th>
                            <th class="px-3 py-2.5 text-right font-medium">Ejecut.</th>
                            <th class="px-3 py-2.5 text-right font-medium">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($executionData as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="sticky left-0 z-10 bg-white px-3 py-2 dark:bg-slate-900">
                                    <div class="min-w-[160px]">
                                        <span class="font-mono text-[10px] text-slate-400">{{ $row['account_code'] }}</span>
                                        <span class="ml-1 text-xs font-medium text-slate-900 dark:text-white">{{ $row['account_name'] }}</span>
                                    </div>
                                </td>
                                @foreach($months as $num => $label)
                                    @php
                                        $monthData = $row['months'][$num] ?? null;
                                        $pct = $monthData ? $monthData['percentage'] : 0;
                                        $cellClass = '';
                                        if ($monthData && $monthData['over_budget']) {
                                            $cellClass = 'bg-red-50 dark:bg-red-950/30';
                                        } elseif ($pct >= 80) {
                                            $cellClass = 'bg-amber-50 dark:bg-amber-950/30';
                                        } elseif ($monthData && $monthData['budgeted'] > 0) {
                                            $cellClass = 'bg-emerald-50/50 dark:bg-emerald-950/20';
                                        }
                                    @endphp
                                    <td class="px-1.5 py-2 text-center {{ $cellClass }}">
                                        @if($monthData)
                                            <div class="tabular-nums">
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ number_format($monthData['budgeted'], 0) }}</p>
                                                <p class="font-semibold {{ $monthData['over_budget'] ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                                    {{ number_format($monthData['executed'], 0) }}
                                                </p>
                                            </div>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">-</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right font-semibold tabular-nums text-slate-700 dark:text-slate-300">
                                    ${{ number_format($row['total_budgeted'], 0) }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold tabular-nums text-slate-900 dark:text-white">
                                    ${{ number_format($row['total_executed'], 0) }}
                                </td>
                                @php
                                    $totalPct = $row['total_budgeted'] > 0
                                        ? round(($row['total_executed'] / $row['total_budgeted']) * 100, 1)
                                        : 0;
                                @endphp
                                <td class="px-3 py-2 text-right tabular-nums font-semibold
                                    {{ $totalPct > 100 ? 'text-red-600 dark:text-red-400' : ($totalPct > 80 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                    {{ number_format($totalPct, 1) }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 15 }}" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No hay datos de ejecucion para este presupuesto.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-6 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded bg-emerald-100 dark:bg-emerald-950/30"></span>
                Dentro del presupuesto
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded bg-amber-100 dark:bg-amber-950/30"></span>
                Cerca del limite (80%+)
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded bg-red-100 dark:bg-red-950/30"></span>
                Sobre presupuesto
            </div>
        </div>
    @else
        <div class="card p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Selecciona un presupuesto</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Elige un presupuesto para ver su ejecucion detallada.</p>
        </div>
    @endif
</div>
