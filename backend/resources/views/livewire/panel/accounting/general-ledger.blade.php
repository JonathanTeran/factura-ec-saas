<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Libro Mayor
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Movimientos contables por cuenta
            </p>
        </div>
        <a href="{{ route('panel.accounting.dashboard') }}" class="btn-secondary">
            Contabilidad
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="relative flex-1">
                <input wire:model.live.debounce.300ms="accountSearch" type="search" placeholder="Buscar cuenta por codigo o nombre..."
                    class="input w-full" autocomplete="off" />
                @if(count($accountResults) > 0)
                    <div class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-800">
                        <ul class="max-h-48 overflow-y-auto py-1">
                            @foreach($accountResults as $result)
                                <li>
                                    <button type="button"
                                        wire:click="selectAccount({{ $result['id'] }})"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                        <span class="font-mono text-xs font-medium text-slate-500">{{ $result['code'] }}</span>
                                        <span class="text-slate-900 dark:text-white">{{ $result['name'] }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <input wire:model.live="dateFrom" type="date" class="input w-full sm:w-40" title="Fecha desde" />
            <input wire:model.live="dateTo" type="date" class="input w-full sm:w-40" title="Fecha hasta" />
        </div>

        {{-- Selected account badge --}}
        @if($selectedAccountCode)
            <div class="mt-3 flex items-center gap-2">
                <span class="text-sm text-slate-500 dark:text-slate-400">Cuenta seleccionada:</span>
                <span class="inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                    <span class="font-mono">{{ $selectedAccountCode }}</span>
                    <span>{{ $selectedAccountName }}</span>
                    <button type="button" wire:click="clearAccount" class="ml-1 text-primary-400 hover:text-primary-600 dark:hover:text-primary-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </div>
        @endif
    </div>

    {{-- Content --}}
    @if($accountId)
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th class="px-4 py-3 font-medium">No. Asiento</th>
                            <th class="px-4 py-3 font-medium">Descripcion</th>
                            <th class="px-4 py-3 font-medium text-right">Debito</th>
                            <th class="px-4 py-3 font-medium text-right">Credito</th>
                            <th class="px-4 py-3 font-medium text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($ledger as $movement)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3 tabular-nums text-slate-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if(isset($movement['entry_id']))
                                        <a href="{{ route('panel.accounting.journal-entries.show', $movement['entry_id']) }}"
                                            class="font-mono text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                            {{ $movement['entry_number'] ?? '-' }}
                                        </a>
                                    @else
                                        <span class="font-mono text-xs text-slate-400">{{ $movement['entry_number'] ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate text-slate-700 dark:text-slate-300" title="{{ $movement['description'] ?? '' }}">
                                    {{ $movement['description'] ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium {{ ($movement['debit'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600' }}">
                                    ${{ number_format($movement['debit'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium {{ ($movement['credit'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600' }}">
                                    ${{ number_format($movement['credit'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold {{ ($movement['balance'] ?? 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    ${{ number_format($movement['balance'] ?? 0, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                    No hay movimientos para esta cuenta en el periodo seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($ledger->isNotEmpty())
                        <tfoot class="border-t-2 border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-800">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">
                                    Totales
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                                    ${{ number_format($totals['debit'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                                    ${{ number_format($totals['credit'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold {{ $totals['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    ${{ number_format($totals['balance'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @else
        <div class="card p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Seleccione una cuenta</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Busque y seleccione una cuenta contable para ver sus movimientos en el libro mayor.
            </p>
        </div>
    @endif
</div>
