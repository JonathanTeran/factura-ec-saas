<div class="space-y-6"
     x-data="{}"
     @download-xml.window="
        const blob = new Blob([$event.detail[0].content], { type: 'application/xml' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = $event.detail[0].filename;
        a.click();
        URL.revokeObjectURL(url);
     ">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('panel.accounting.tax-forms') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400" wire:navigate>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver a formularios
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Anexo Transaccional Simplificado (ATS)
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Genera el ATS para presentacion al SRI
            </p>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="card p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-40">
                <label class="form-label">Ano</label>
                <select wire:model.live="year" class="input w-full">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-48">
                <label class="form-label">Mes</label>
                <select wire:model.live="month" class="input w-full">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="generate" class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                </svg>
                Generar ATS
            </button>
        </div>
    </div>

    {{-- Summary Stats --}}
    @if($isGenerated)
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
            <div class="stat-card">
                <div class="stat-card-glow bg-emerald-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['ventas'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ventas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow bg-blue-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['compras'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Compras</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow bg-violet-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['retenciones'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Retenciones</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow bg-amber-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['anulados'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Anulados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow bg-primary-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-lg">${{ number_format($stats['total_ventas'], 2) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total ventas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-glow bg-rose-500"></div>
                <div>
                    <p class="stat-value tabular-nums text-lg">${{ number_format($stats['total_compras'], 2) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total compras</p>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-700">
                <nav class="flex -mb-px">
                    @foreach(['ventas' => 'Ventas', 'compras' => 'Compras', 'retenciones' => 'Retenciones', 'anulados' => 'Anulados'] as $tabKey => $tabLabel)
                        <button wire:click="setTab('{{ $tabKey }}')"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors
                                {{ $activeTab === $tabKey
                                    ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300' }}">
                            {{ $tabLabel }}
                            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs tabular-nums dark:bg-slate-800">
                                {{ count($atsData[$tabKey] ?? []) }}
                            </span>
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Tab: Ventas --}}
            @if($activeTab === 'ventas')
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Tipo ID</th>
                                <th class="px-4 py-3 font-medium">Identificacion</th>
                                <th class="px-4 py-3 font-medium">Tipo Comp.</th>
                                <th class="px-4 py-3 text-right font-medium">Base 0%</th>
                                <th class="px-4 py-3 text-right font-medium">Base Grav.</th>
                                <th class="px-4 py-3 text-right font-medium">IVA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($atsData['ventas'] ?? [] as $venta)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $venta['tpIdCliente'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $venta['idCliente'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5">{{ $venta['tipoComprobante'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($venta['baseImponible'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($venta['baseImpGrav'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($venta['montoIva'] ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No hay ventas en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Tab: Compras --}}
            @if($activeTab === 'compras')
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Tipo ID</th>
                                <th class="px-4 py-3 font-medium">Proveedor</th>
                                <th class="px-4 py-3 font-medium">Comprobante</th>
                                <th class="px-4 py-3 font-medium">Fecha</th>
                                <th class="px-4 py-3 text-right font-medium">Base 0%</th>
                                <th class="px-4 py-3 text-right font-medium">Base Grav.</th>
                                <th class="px-4 py-3 text-right font-medium">IVA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($atsData['compras'] ?? [] as $compra)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $compra['tpIdProv'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $compra['idProv'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ ($compra['establecimiento'] ?? '') . '-' . ($compra['puntoEmision'] ?? '') . '-' . ($compra['secuencial'] ?? '') }}</td>
                                    <td class="px-4 py-2.5 text-xs">{{ $compra['fechaEmision'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($compra['baseImponible'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($compra['baseImpGrav'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">${{ number_format($compra['montoIva'] ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No hay compras en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Tab: Retenciones --}}
            @if($activeTab === 'retenciones')
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Establecimiento</th>
                                <th class="px-4 py-3 font-medium">Punto Emision</th>
                                <th class="px-4 py-3 font-medium">Secuencial</th>
                                <th class="px-4 py-3 font-medium">Autorizacion</th>
                                <th class="px-4 py-3 font-medium">Fecha Emision</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($atsData['retenciones'] ?? [] as $ret)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $ret['estabRetencion1'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $ret['ptoEmiRetencion1'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $ret['secRetencion1'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs truncate max-w-[200px]">{{ $ret['autRetencion1'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-xs">{{ $ret['fechaEmiRet1'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No hay retenciones en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Tab: Anulados --}}
            @if($activeTab === 'anulados')
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Tipo Comprobante</th>
                                <th class="px-4 py-3 font-medium">Establecimiento</th>
                                <th class="px-4 py-3 font-medium">Punto Emision</th>
                                <th class="px-4 py-3 font-medium">Sec. Inicio</th>
                                <th class="px-4 py-3 font-medium">Sec. Fin</th>
                                <th class="px-4 py-3 font-medium">Autorizacion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($atsData['anulados'] ?? [] as $anulado)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-4 py-2.5">{{ $anulado['tipoComprobante'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $anulado['establecimiento'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $anulado['puntoEmision'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $anulado['secuencialInicio'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs">{{ $anulado['secuencialFin'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs truncate max-w-[200px]">{{ $anulado['autorizacion'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No hay comprobantes anulados en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Download Button --}}
        <div class="flex justify-end">
            <button wire:click="downloadXml" class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Descargar XML
            </button>
        </div>
    @endif
</div>
