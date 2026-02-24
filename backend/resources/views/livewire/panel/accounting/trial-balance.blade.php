<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Balance de Comprobacion
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Verificacion de saldos deudores y acreedores
            </p>
        </div>
        <a href="{{ route('panel.accounting.dashboard') }}" class="btn-secondary">
            Contabilidad
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            @if($companies->count() > 1)
                <select wire:model.live="companyId" class="input w-full sm:w-48">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->business_name }}</option>
                    @endforeach
                </select>
            @endif
            <input wire:model.live="dateFrom" type="date" class="input w-full sm:w-40" title="Fecha desde" />
            <input wire:model.live="dateTo" type="date" class="input w-full sm:w-40" title="Fecha hasta" />
        </div>
    </div>

    {{-- Balance indicator --}}
    @if(count($trialBalance['data']) > 0)
        <div class="flex items-center gap-2">
            @if(bccomp((string) $trialBalance['totals']['debit'], (string) $trialBalance['totals']['credit'], 2) === 0)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    Balance cuadrado
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    Balance descuadrado - Diferencia: ${{ number_format(abs($trialBalance['totals']['debit'] - $trialBalance['totals']['credit']), 2) }}
                </span>
            @endif
        </div>
    @endif

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Codigo</th>
                        <th class="px-4 py-3 font-medium">Cuenta</th>
                        <th class="px-4 py-3 font-medium text-right">Saldo Debito</th>
                        <th class="px-4 py-3 font-medium text-right">Saldo Credito</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($trialBalance['data'] as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-mono text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ $row['code'] }}
                            </td>
                            <td class="px-4 py-3 text-slate-900 dark:text-white">
                                {{ $row['name'] }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium {{ ($row['debit'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600' }}">
                                ${{ number_format($row['debit'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium {{ ($row['credit'] ?? 0) > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600' }}">
                                ${{ number_format($row['credit'] ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-slate-500">
                                No hay datos para el periodo seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($trialBalance['data']) > 0)
                    <tfoot class="border-t-2 border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-800">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">
                                Totales
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                                ${{ number_format($trialBalance['totals']['debit'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                                ${{ number_format($trialBalance['totals']['credit'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
