<div class="space-y-6">
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
                {{ $formLabel }}
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Genera y guarda el formulario tributario
            </p>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="card p-5">
        <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Periodo</h2>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:w-40">
                <label class="form-label">Ano</label>
                <select wire:model.live="year" class="input w-full">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            @if(!$isAnnual)
                <div class="w-full sm:w-48">
                    <label class="form-label">Mes</label>
                    <select wire:model.live="month" class="input w-full">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button wire:click="generatePreview" class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Generar vista previa
            </button>
        </div>
    </div>

    {{-- Preview --}}
    @if($showPreview && $previewData)
        <div class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Vista Previa</h2>
                <div class="flex gap-2">
                    <button wire:click="save" class="btn-primary">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Guardar formulario
                    </button>
                </div>
            </div>

            {{-- Company Info --}}
            <div class="mb-6 rounded-lg bg-slate-50 p-4 dark:bg-slate-800">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">RUC</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $previewData['company']['ruc'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Razon Social</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $previewData['company']['business_name'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Periodo</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                            @if(!$isAnnual)
                                {{ $months[$previewData['month'] ?? $month] ?? '' }}
                            @endif
                            {{ $previewData['year'] ?? $year }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- F104 - IVA Details --}}
            @if(($previewData['form_type'] ?? '') === 'f104')
                <div class="space-y-4">
                    {{-- Ventas --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Ventas del Periodo</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">Concepto</th>
                                        <th class="px-4 py-2 text-right font-medium">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <tr>
                                        <td class="px-4 py-2">Ventas tarifa 0%</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['ventas']['subtotal_0'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Ventas tarifa 12%</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['ventas']['subtotal_12'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Ventas tarifa 15%</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['ventas']['subtotal_15'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr class="font-semibold">
                                        <td class="px-4 py-2">IVA generado en ventas</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['ventas']['iva_generado'] ?? 0, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Notas de credito --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Notas de Credito</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <tr>
                                        <td class="px-4 py-2">Subtotal notas de credito</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['notas_credito']['subtotal'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">IVA notas de credito</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['notas_credito']['iva'] ?? 0, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Compras --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Compras del Periodo</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <tr>
                                        <td class="px-4 py-2">Compras tarifa 0%</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['compras']['subtotal_0'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Compras gravadas</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['compras']['subtotal_gravado'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">IVA en compras</td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($previewData['compras']['iva_compras'] ?? 0, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Liquidacion --}}
                    <div class="rounded-lg border-2 border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-950/30">
                        <h3 class="mb-2 text-sm font-semibold text-primary-700 dark:text-primary-300">Liquidacion del Impuesto</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>IVA por ventas</span>
                                <span class="font-semibold tabular-nums">${{ number_format($previewData['liquidacion']['iva_ventas'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>(-) Credito tributario</span>
                                <span class="font-semibold tabular-nums">${{ number_format($previewData['liquidacion']['credito_tributario'] ?? 0, 2) }}</span>
                            </div>
                            <hr class="border-primary-200 dark:border-primary-800">
                            <div class="flex justify-between text-base font-bold">
                                <span>Impuesto causado</span>
                                <span class="tabular-nums">${{ number_format($previewData['liquidacion']['impuesto_causado'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- F103 - Retenciones Details --}}
            @if(($previewData['form_type'] ?? '') === 'f103')
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Casilleros de Retenciones</h3>
                    @if(!empty($previewData['casilleros']))
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">Casillero</th>
                                        <th class="px-4 py-2 text-right font-medium">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($previewData['casilleros'] as $key => $value)
                                        <tr>
                                            <td class="px-4 py-2 font-mono text-xs">{{ $key }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums">${{ number_format($value, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                Total retenciones: <span class="tabular-nums">${{ number_format($previewData['total_retenciones'] ?? 0, 2) }}</span>
                            </p>
                        </div>
                    @else
                        <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            No se encontraron retenciones para este periodo.
                        </p>
                    @endif
                </div>
            @endif

            {{-- F101/F102 - IR Details --}}
            @if(in_array($previewData['form_type'] ?? '', ['f101', 'f102']))
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Casilleros del Formulario</h3>
                    @if(!empty($previewData['casilleros']))
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">Codigo Casillero</th>
                                        <th class="px-4 py-2 text-right font-medium">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($previewData['casilleros'] as $code => $value)
                                        <tr>
                                            <td class="px-4 py-2 font-mono text-xs">{{ $code }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums">${{ number_format($value, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            No se encontraron datos para este periodo. Verifique que las cuentas tengan codigos de formulario asignados.
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @elseif($showPreview && !$previewData)
        <div class="card p-8 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                No se pudieron generar datos para este formulario y periodo.
            </p>
        </div>
    @endif
</div>
