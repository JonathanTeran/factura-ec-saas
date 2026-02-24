<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Resumen de tu actividad de facturación</p>
    </div>

    <!-- Stats Grid -->
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Documents this month -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow dark:bg-gray-800 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Documentos del mes</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                {{ number_format($stats['documents_count']) }}
            </dd>
            @if($stats['documents_count_last'] > 0)
            <dd class="mt-2 flex items-center text-sm">
                @php
                    $diff = $stats['documents_count'] - $stats['documents_count_last'];
                    $percentage = round(($diff / $stats['documents_count_last']) * 100);
                @endphp
                @if($diff >= 0)
                <span class="flex items-center text-green-600">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ abs($percentage) }}%
                </span>
                @else
                <span class="flex items-center text-red-600">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ abs($percentage) }}%
                </span>
                @endif
                <span class="ml-2 text-gray-500 dark:text-gray-400">vs mes anterior</span>
            </dd>
            @endif
        </div>

        <!-- Total billed -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow dark:bg-gray-800 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total facturado</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                ${{ number_format($stats['documents_total'], 2) }}
            </dd>
            @if($stats['documents_total_last'] > 0)
            <dd class="mt-2 flex items-center text-sm">
                @php
                    $diff = $stats['documents_total'] - $stats['documents_total_last'];
                    $percentage = round(($diff / $stats['documents_total_last']) * 100);
                @endphp
                @if($diff >= 0)
                <span class="flex items-center text-green-600">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ abs($percentage) }}%
                </span>
                @else
                <span class="flex items-center text-red-600">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ abs($percentage) }}%
                </span>
                @endif
                <span class="ml-2 text-gray-500 dark:text-gray-400">vs mes anterior</span>
            </dd>
            @endif
        </div>

        <!-- Pending -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow dark:bg-gray-800 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Pendientes</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">
                {{ number_format($stats['pending']) }}
            </dd>
            <dd class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Documentos por procesar
            </dd>
        </div>

        <!-- Rejected -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow dark:bg-gray-800 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Rechazados</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">
                {{ number_format($stats['rejected']) }}
            </dd>
            <dd class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Este mes
            </dd>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Documents -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Documentos Recientes</h3>
                    <a href="{{ route('tenant.documents.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        Ver todos
                    </a>
                </div>
            </div>
            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentDocuments as $doc)
                <li class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $doc['document_number'] }}
                            </p>
                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                                {{ $doc['customer_name'] }}
                            </p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0 flex-col items-end">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($doc['total'], 2) }}
                            </p>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                @if($doc['status_color'] === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($doc['status_color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($doc['status_color'] === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                @endif">
                                {{ $doc['status_label'] }}
                            </span>
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay documentos recientes</p>
                    <a href="{{ route('tenant.invoices.create') }}" class="mt-2 inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-500">
                        Crear tu primera factura
                        <svg class="ml-1 size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </li>
                @endforelse
            </ul>
        </div>

        <!-- Monthly Chart -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Facturación {{ now()->year }}</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="h-64" x-data="{
                    data: @js($monthlyData),
                    init() {
                        // Simple bar chart with Alpine
                        const maxTotal = Math.max(...this.data.map(d => d.total), 1);
                        this.data = this.data.map(d => ({
                            ...d,
                            height: (d.total / maxTotal) * 100
                        }));
                    }
                }">
                    <div class="flex h-full items-end justify-between gap-1">
                        <template x-for="(item, index) in data" :key="index">
                            <div class="flex flex-1 flex-col items-center">
                                <div class="relative w-full">
                                    <div class="absolute bottom-0 w-full rounded-t bg-primary-500 transition-all duration-300"
                                        :style="'height: ' + item.height + '%'"
                                        :title="'$' + item.total.toLocaleString()">
                                    </div>
                                </div>
                                <span class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="item.month"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Acciones Rápidas</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('tenant.invoices.create') }}"
                class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm transition hover:border-primary-500 hover:shadow dark:border-gray-700 dark:bg-gray-800">
                <svg class="mb-2 size-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Nueva Factura</span>
            </a>

            <a href="{{ route('tenant.customers.create') }}"
                class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm transition hover:border-primary-500 hover:shadow dark:border-gray-700 dark:bg-gray-800">
                <svg class="mb-2 size-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Nuevo Cliente</span>
            </a>

            <a href="{{ route('tenant.products.create') }}"
                class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm transition hover:border-primary-500 hover:shadow dark:border-gray-700 dark:bg-gray-800">
                <svg class="mb-2 size-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Nuevo Producto</span>
            </a>

            <a href="{{ route('tenant.reports.index') }}"
                class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm transition hover:border-primary-500 hover:shadow dark:border-gray-700 dark:bg-gray-800">
                <svg class="mb-2 size-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Reportes</span>
            </a>
        </div>
    </div>
</div>
