<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Reportes
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Analiza las ventas, impuestos y rendimiento de tu negocio
            </p>
        </div>

        {{-- Export buttons --}}
        <div class="flex gap-2">
            <button wire:click="exportReport('excel')"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Excel
            </button>
            <button wire:click="exportReport('pdf')"
                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/25 transition-all hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                PDF
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Ventas hoy</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        ${{ number_format($dashboardStats['sales_today'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            @if(isset($dashboardStats['sales_today_change']))
                <p class="mt-2 text-sm {{ $dashboardStats['sales_today_change'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $dashboardStats['sales_today_change'] >= 0 ? '+' : '' }}{{ number_format($dashboardStats['sales_today_change'], 1) }}% vs ayer
                </p>
            @endif
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Ventas este mes</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        ${{ number_format($dashboardStats['sales_month'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Documentos emitidos</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ number_format($dashboardStats['documents_month'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">IVA recaudado</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        ${{ number_format($dashboardStats['tax_month'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Date Range Preset --}}
            <div>
                <label for="dateRange" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Período
                </label>
                <select wire:model.live="dateRange" id="dateRange"
                        class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="this_week">Esta semana</option>
                    <option value="last_week">Semana pasada</option>
                    <option value="this_month">Este mes</option>
                    <option value="last_month">Mes pasado</option>
                    <option value="this_quarter">Este trimestre</option>
                    <option value="this_year">Este año</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>

            {{-- Start Date --}}
            <div>
                <label for="startDate" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Desde
                </label>
                <input wire:model.live="startDate" type="date" id="startDate"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
            </div>

            {{-- End Date --}}
            <div>
                <label for="endDate" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Hasta
                </label>
                <input wire:model.live="endDate" type="date" id="endDate"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
            </div>

            {{-- Report Type --}}
            <div>
                <label for="reportType" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Tipo de reporte
                </label>
                <select wire:model.live="reportType" id="reportType"
                        class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                    <option value="sales">Ventas</option>
                    <option value="tax">Impuestos</option>
                    <option value="customers">Top Clientes</option>
                    <option value="products">Top Productos</option>
                    <option value="status">Por Estado</option>
                    <option value="comparison">Comparativo</option>
                    <option value="ats">ATS (Anexo Transaccional)</option>
                    <option value="withholdings">Retenciones</option>
                </select>
            </div>

            {{-- Group By --}}
            @if($reportType === 'sales')
                <div>
                    <label for="groupBy" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Agrupar por
                    </label>
                    <select wire:model.live="groupBy" id="groupBy"
                            class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                        <option value="day">Día</option>
                        <option value="week">Semana</option>
                        <option value="month">Mes</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- Report Content --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        @if($reportType === 'sales')
            {{-- Sales Report --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Reporte de Ventas</h3>

            @if(isset($reportData['summary']))
                <div class="mb-6 grid gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Total Ventas</p>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">${{ number_format($reportData['summary']['total'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Subtotal</p>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">${{ number_format($reportData['summary']['subtotal'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                        <p class="text-sm text-slate-500 dark:text-slate-400">IVA</p>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">${{ number_format($reportData['summary']['tax'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Documentos</p>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ number_format($reportData['summary']['count'] ?? 0) }}</p>
                    </div>
                </div>
            @endif

            @if(isset($reportData['data']) && count($reportData['data']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Fecha</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Documentos</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Subtotal</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">IVA</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($reportData['data'] as $row)
                                <tr>
                                    <td class="py-4 text-sm text-slate-900 dark:text-white">{{ $row['date'] ?? $row['period'] ?? '-' }}</td>
                                    <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">{{ $row['count'] ?? 0 }}</td>
                                    <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">${{ number_format($row['subtotal'] ?? 0, 2) }}</td>
                                    <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">${{ number_format($row['tax'] ?? 0, 2) }}</td>
                                    <td class="py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">${{ number_format($row['total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No hay datos para el período seleccionado</p>
                </div>
            @endif

        @elseif($reportType === 'tax')
            {{-- Tax Report --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Reporte de Impuestos</h3>

            @if(isset($reportData['summary']))
                <div class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                        <p class="text-sm text-blue-600 dark:text-blue-400">Base Imponible 0%</p>
                        <p class="mt-1 text-xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($reportData['summary']['subtotal_0'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-900/20">
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">Base Imponible 12%</p>
                        <p class="mt-1 text-xl font-bold text-emerald-900 dark:text-emerald-100">${{ number_format($reportData['summary']['subtotal_12'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-900/20">
                        <p class="text-sm text-amber-600 dark:text-amber-400">IVA Total</p>
                        <p class="mt-1 text-xl font-bold text-amber-900 dark:text-amber-100">${{ number_format($reportData['summary']['total_tax'] ?? 0, 2) }}</p>
                    </div>
                </div>
            @endif

        @elseif($reportType === 'customers')
            {{-- Top Customers --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Top Clientes</h3>

            @if(isset($reportData['data']) && count($reportData['data']) > 0)
                <div class="space-y-4">
                    @foreach($reportData['data'] as $index => $customer)
                        <div class="flex items-center gap-4 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $customer['name'] ?? 'N/A' }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $customer['identification'] ?? '' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900 dark:text-white">${{ number_format($customer['total'] ?? 0, 2) }}</p>
                                <p class="text-sm text-slate-500">{{ $customer['documents_count'] ?? 0 }} documentos</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">No hay datos de clientes para el período</p>
                </div>
            @endif

        @elseif($reportType === 'products')
            {{-- Top Products --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Top Productos</h3>

            @if(isset($reportData['data']) && count($reportData['data']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">#</th>
                                <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Producto</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Cantidad</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($reportData['data'] as $index => $product)
                                <tr>
                                    <td class="py-4 text-sm font-medium text-slate-900 dark:text-white">{{ $index + 1 }}</td>
                                    <td class="py-4">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $product['name'] ?? 'N/A' }}</p>
                                        <p class="text-xs text-slate-500">{{ $product['code'] ?? '' }}</p>
                                    </td>
                                    <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">{{ number_format($product['quantity'] ?? 0, 2) }}</td>
                                    <td class="py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">${{ number_format($product['total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">No hay datos de productos para el período</p>
                </div>
            @endif

        @elseif($reportType === 'status')
            {{-- Documents by Status --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Documentos por Estado</h3>

            @if(isset($reportData['data']) && count($reportData['data']) > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($reportData['data'] as $status)
                        @php
                            $colors = [
                                'authorized' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
                                'pending' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
                                'rejected' => 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400',
                                'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                'canceled' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                            ];
                            $labels = [
                                'authorized' => 'Autorizados',
                                'pending' => 'Pendientes',
                                'rejected' => 'Rechazados',
                                'draft' => 'Borradores',
                                'canceled' => 'Anulados',
                            ];
                        @endphp
                        <div class="rounded-xl {{ $colors[$status['status']] ?? 'bg-slate-100 dark:bg-slate-700' }} p-4">
                            <p class="text-sm font-medium">{{ $labels[$status['status']] ?? $status['status'] }}</p>
                            <p class="mt-1 text-2xl font-bold">{{ number_format($status['count'] ?? 0) }}</p>
                            <p class="mt-1 text-sm opacity-75">${{ number_format($status['total'] ?? 0, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">No hay datos para el período</p>
                </div>
            @endif

        @elseif($reportType === 'comparison')
            {{-- Period Comparison --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Comparativo de Períodos</h3>

            @if(isset($reportData['current']) && isset($reportData['previous']))
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="rounded-xl bg-blue-50 p-6 dark:bg-blue-900/20">
                        <h4 class="text-sm font-medium text-blue-600 dark:text-blue-400">Período actual</h4>
                        <p class="mt-2 text-3xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($reportData['current']['total'] ?? 0, 2) }}</p>
                        <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">{{ $reportData['current']['count'] ?? 0 }} documentos</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 p-6 dark:bg-slate-700">
                        <h4 class="text-sm font-medium text-slate-600 dark:text-slate-400">Período anterior</h4>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">${{ number_format($reportData['previous']['total'] ?? 0, 2) }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $reportData['previous']['count'] ?? 0 }} documentos</p>
                    </div>
                </div>

                @if(isset($reportData['change_percent']))
                    <div class="mt-6 rounded-xl {{ $reportData['change_percent'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }} p-4 text-center">
                        <p class="text-lg font-bold {{ $reportData['change_percent'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $reportData['change_percent'] >= 0 ? '+' : '' }}{{ number_format($reportData['change_percent'], 1) }}%
                        </p>
                        <p class="text-sm {{ $reportData['change_percent'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $reportData['change_percent'] >= 0 ? 'Incremento' : 'Decremento' }} respecto al período anterior
                        </p>
                    </div>
                @endif
            @else
                <div class="py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">No hay suficientes datos para comparar</p>
                </div>
            @endif

        @elseif($reportType === 'ats')
            {{-- ATS Report --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Anexo Transaccional Simplificado (ATS)</h3>

            {{-- ATS Filters --}}
            <div class="mb-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="atsYear" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Año
                    </label>
                    <select wire:model.live="atsYear" id="atsYear"
                            class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="atsMonth" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Mes
                    </label>
                    <select wire:model.live="atsMonth" id="atsMonth"
                            class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>
            </div>

            {{-- ATS Summary Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-900/20">
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Ventas</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ number_format($reportData['ventas']['count'] ?? 0) }}</p>
                    <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">${{ number_format($reportData['ventas']['total'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Compras</p>
                    <p class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($reportData['compras']['count'] ?? 0) }}</p>
                    <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">${{ number_format($reportData['compras']['total'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl bg-violet-50 p-4 dark:bg-violet-900/20">
                    <p class="text-sm font-medium text-violet-600 dark:text-violet-400">Retenciones Emitidas</p>
                    <p class="mt-1 text-2xl font-bold text-violet-900 dark:text-violet-100">{{ number_format($reportData['retenciones_emitidas']['count'] ?? 0) }}</p>
                    <p class="mt-1 text-sm text-violet-600 dark:text-violet-400">${{ number_format($reportData['retenciones_emitidas']['total'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-100 p-4 dark:bg-slate-700">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Anulados</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($reportData['anulados']['count'] ?? 0) }}</p>
                </div>
            </div>

        @elseif($reportType === 'withholdings')
            {{-- Withholdings Report --}}
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Reporte de Retenciones</h3>

            @if(isset($reportData['summary']))
                <div class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                        <p class="text-sm text-blue-600 dark:text-blue-400">Ret. Renta</p>
                        <p class="mt-1 text-xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($reportData['summary']['renta'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-900/20">
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">Ret. IVA</p>
                        <p class="mt-1 text-xl font-bold text-emerald-900 dark:text-emerald-100">${{ number_format($reportData['summary']['iva'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-violet-50 p-4 dark:bg-violet-900/20">
                        <p class="text-sm text-violet-600 dark:text-violet-400">Ret. ISD</p>
                        <p class="mt-1 text-xl font-bold text-violet-900 dark:text-violet-100">${{ number_format($reportData['summary']['isd'] ?? 0, 2) }}</p>
                    </div>
                </div>
            @endif

            @if(isset($reportData['data']) && count($reportData['data']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Tipo Impuesto</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Cantidad</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Base Total</th>
                                <th class="pb-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Monto Retenido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($reportData['data'] as $row)
                                <tr>
                                    <td class="py-4 text-sm text-slate-900 dark:text-white">{{ $row['tax_type'] ?? '-' }}</td>
                                    <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">{{ number_format($row['count'] ?? 0) }}</td>
                                    <td class="py-4 text-right text-sm text-slate-600 dark:text-slate-400">${{ number_format($row['base_total'] ?? 0, 2) }}</td>
                                    <td class="py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">${{ number_format($row['withheld_total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No hay datos de retenciones para el período seleccionado</p>
                </div>
            @endif
        @endif
    </div>
</div>
