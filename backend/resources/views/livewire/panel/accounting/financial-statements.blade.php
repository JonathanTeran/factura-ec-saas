<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Estados Financieros
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Reportes financieros de la empresa
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
            <select wire:model.live="reportType" class="input w-full sm:w-56">
                <option value="balance_sheet">Estado de Situacion Financiera</option>
                <option value="income_statement">Estado de Resultados</option>
                <option value="cash_flow">Flujo de Efectivo</option>
            </select>
            <input wire:model.live="dateFrom" type="date" class="input w-full sm:w-40" title="Fecha desde" />
            <input wire:model.live="dateTo" type="date" class="input w-full sm:w-40" title="Fecha hasta" />
        </div>
    </div>

    {{-- Report Content --}}
    @if($report)
        <div class="card p-6">
            <h2 class="mb-6 text-center text-xl font-bold text-slate-900 dark:text-white">
                {{ $reportTitle }}
            </h2>
            <p class="mb-6 text-center text-sm text-slate-500 dark:text-slate-400">
                @if($reportType === 'balance_sheet')
                    Al {{ \Carbon\Carbon::parse($dateTo ?: now())->format('d/m/Y') }}
                @else
                    Del {{ \Carbon\Carbon::parse($dateFrom ?: now()->startOfYear())->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($dateTo ?: now())->format('d/m/Y') }}
                @endif
            </p>

            @if($reportType === 'balance_sheet')
                {{-- Balance Sheet: Assets, Liabilities, Equity --}}
                @foreach(['assets' => 'Activos', 'liabilities' => 'Pasivos', 'equity' => 'Patrimonio'] as $key => $label)
                    @if(isset($report[$key]))
                        <div class="mb-6">
                            <h3 class="mb-3 border-b-2 border-slate-200 pb-2 text-lg font-bold text-slate-800 dark:border-slate-700 dark:text-slate-200">
                                {{ $label }}
                            </h3>
                            <div class="space-y-1">
                                @if(isset($report[$key]['accounts']))
                                    @foreach($report[$key]['accounts'] as $account)
                                        <div class="flex items-center justify-between px-4 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ $account['name'] }}</span>
                                            <span class="tabular-nums text-sm font-medium text-slate-900 dark:text-white">
                                                ${{ number_format($account['amount'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-2 flex items-center justify-between border-t border-slate-200 px-4 py-2 dark:border-slate-700">
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Total {{ $label }}</span>
                                <span class="tabular-nums text-sm font-bold text-slate-900 dark:text-white">
                                    ${{ number_format($report[$key]['total'] ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach

            @elseif($reportType === 'income_statement')
                {{-- Income Statement: Revenue, Costs, Expenses, Net Income --}}
                @foreach(['revenue' => 'Ingresos', 'costs' => 'Costos', 'expenses' => 'Gastos'] as $key => $label)
                    @if(isset($report[$key]))
                        <div class="mb-6">
                            <h3 class="mb-3 border-b-2 border-slate-200 pb-2 text-lg font-bold text-slate-800 dark:border-slate-700 dark:text-slate-200">
                                {{ $label }}
                            </h3>
                            <div class="space-y-1">
                                @if(isset($report[$key]['accounts']))
                                    @foreach($report[$key]['accounts'] as $account)
                                        <div class="flex items-center justify-between px-4 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ $account['name'] }}</span>
                                            <span class="tabular-nums text-sm font-medium text-slate-900 dark:text-white">
                                                ${{ number_format($account['amount'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-2 flex items-center justify-between border-t border-slate-200 px-4 py-2 dark:border-slate-700">
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Total {{ $label }}</span>
                                <span class="tabular-nums text-sm font-bold text-slate-900 dark:text-white">
                                    ${{ number_format($report[$key]['total'] ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
                {{-- Net Income --}}
                @if(isset($report['net_income']))
                    <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-100 px-5 py-3 dark:bg-slate-800">
                        <span class="text-base font-bold text-slate-900 dark:text-white">Utilidad Neta</span>
                        <span class="tabular-nums text-lg font-bold {{ ($report['net_income'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            ${{ number_format($report['net_income'] ?? 0, 2) }}
                        </span>
                    </div>
                @endif

            @elseif($reportType === 'cash_flow')
                {{-- Cash Flow: Operating, Investing, Financing --}}
                @foreach(['operating' => 'Actividades Operativas', 'investing' => 'Actividades de Inversion', 'financing' => 'Actividades de Financiamiento'] as $key => $label)
                    @if(isset($report[$key]))
                        <div class="mb-6">
                            <h3 class="mb-3 border-b-2 border-slate-200 pb-2 text-lg font-bold text-slate-800 dark:border-slate-700 dark:text-slate-200">
                                {{ $label }}
                            </h3>
                            <div class="space-y-1">
                                @if(isset($report[$key]['accounts']))
                                    @foreach($report[$key]['accounts'] as $account)
                                        <div class="flex items-center justify-between px-4 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ $account['name'] }}</span>
                                            <span class="tabular-nums text-sm font-medium {{ ($account['amount'] ?? 0) >= 0 ? 'text-slate-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                                                ${{ number_format($account['amount'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-2 flex items-center justify-between border-t border-slate-200 px-4 py-2 dark:border-slate-700">
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Total {{ $label }}</span>
                                <span class="tabular-nums text-sm font-bold text-slate-900 dark:text-white">
                                    ${{ number_format($report[$key]['total'] ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
                {{-- Net Cash Variation --}}
                @if(isset($report['net_variation']))
                    <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-100 px-5 py-3 dark:bg-slate-800">
                        <span class="text-base font-bold text-slate-900 dark:text-white">Variacion Neta de Efectivo</span>
                        <span class="tabular-nums text-lg font-bold {{ ($report['net_variation'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            ${{ number_format($report['net_variation'] ?? 0, 2) }}
                        </span>
                    </div>
                @endif
            @endif
        </div>
    @else
        <div class="card p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Seleccione los parametros del reporte</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Elija la empresa, tipo de reporte y rango de fechas para generar el estado financiero.
            </p>
        </div>
    @endif
</div>
