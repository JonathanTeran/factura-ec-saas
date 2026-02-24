<div>
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <p class="text-sm font-medium text-primary-600 dark:text-primary-400 mb-1">
                {{ now()->isoFormat('dddd, D [de] MMMM') }}
            </p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Buenos {{ now()->hour < 12 ? 'días' : (now()->hour < 18 ? 'tardes' : 'noches') }},
                {{ Str::words(auth()->user()->name, 1, '') }}
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Resumen de actividad en <span
                    class="font-medium text-slate-700 dark:text-slate-300">{{ auth()->user()->tenant?->name }}</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Period selector --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all duration-200 shadow-sm">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <span>{{ $period === 'week' ? 'Esta semana' : ($period === 'month' ? 'Este mes' : 'Este año') }}</span>
                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div x-show="open" x-cloak @click.away="open = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    class="dropdown-menu absolute right-0 z-10 mt-2 w-44 origin-top-right p-1">
                    <button wire:click="$set('period', 'week')" @click="open = false"
                        class="dropdown-item w-full {{ $period === 'week' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : '' }}">
                        Esta semana
                    </button>
                    <button wire:click="$set('period', 'month')" @click="open = false"
                        class="dropdown-item w-full {{ $period === 'month' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : '' }}">
                        Este mes
                    </button>
                    <button wire:click="$set('period', 'year')" @click="open = false"
                        class="dropdown-item w-full {{ $period === 'year' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : '' }}">
                        Este año
                    </button>
                </div>
            </div>

            <a href="{{ route('panel.invoices.create') }}" class="btn-primary shadow-lg shadow-primary-500/20">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva Factura
            </a>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8 stagger-children">
        {{-- Documents stat --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Documentos</p>
                    <p class="mt-2 stat-value tabular-nums">
                        {{ number_format($this->stats['documents']['value']) }}
                    </p>
                    <div class="mt-2 flex items-center gap-1.5">
                        @if ($this->stats['documents']['change'] != 0)
                            @php $isPositive = $this->stats['documents']['changeType'] === 'positive'; @endphp
                            <span
                                class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-xs font-semibold {{ $isPositive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' }}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                    stroke="currentColor">
                                    @if ($isPositive)
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" />
                                    @endif
                                </svg>
                                {{ abs($this->stats['documents']['change']) }}%
                            </span>
                        @endif
                    </div>
                </div>
                <div
                    class="stat-icon bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Revenue stat --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Ingresos</p>
                    <p class="mt-2 stat-value tabular-nums">
                        ${{ number_format($this->stats['revenue']['value'], 2) }}
                    </p>
                    <div class="mt-2 flex items-center gap-1.5">
                        @if ($this->stats['revenue']['change'] != 0)
                            @php $isPositive = $this->stats['revenue']['changeType'] === 'positive'; @endphp
                            <span
                                class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-xs font-semibold {{ $isPositive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' }}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                    stroke="currentColor">
                                    @if ($isPositive)
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" />
                                    @endif
                                </svg>
                                {{ abs($this->stats['revenue']['change']) }}%
                            </span>
                        @endif
                    </div>
                </div>
                <div
                    class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/25 group-hover:shadow-emerald-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Customers stat --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-violet-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Clientes</p>
                    <p class="mt-2 stat-value tabular-nums">
                        {{ number_format($this->stats['customers']['value']) }}
                    </p>
                    <div class="mt-2">
                        <span class="badge-primary">
                            <span class="badge-dot bg-primary-500"></span>
                            +{{ $this->stats['customers']['new'] }} nuevos
                        </span>
                    </div>
                </div>
                <div
                    class="stat-icon bg-gradient-to-br from-violet-500 to-violet-700 shadow-lg shadow-violet-500/25 group-hover:shadow-violet-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Pending stat --}}
        <div class="stat-card group">
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pendientes</p>
                    <p class="mt-2 stat-value tabular-nums">
                        {{ number_format($this->stats['pending']['value']) }}
                    </p>
                    <div class="mt-2">
                        <a href="{{ route('panel.documents.index') }}?status=pending"
                            class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                            Ver documentos
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>
                </div>
                <div
                    class="stat-icon bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-500/25 group-hover:shadow-amber-500/40 transition-shadow duration-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">
        {{-- Revenue trend chart --}}
        <div class="lg:col-span-2 card">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Tendencia de 7 dias</h2>
                </div>
            </div>
            <div class="p-6">
                <div class="h-64" x-data="chartComponent({
                    type: 'line',
                    data: {
                        labels: @js($this->chartData['labels'] ?? []),
                        datasets: [{
                                label: 'Ingresos ($)',
                                data: @js($this->chartData['revenue'] ?? []),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2.5,
                                pointRadius: 4,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Facturas',
                                data: @js($this->chartData['invoices'] ?? []),
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.05)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointBackgroundColor: '#6366f1',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 5,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        scales: {
                            y: {
                                position: 'left',
                                ticks: { callback: function(v) { return '$' + v.toLocaleString() } }
                            },
                            y1: {
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { stepSize: 1 }
                            }
                        },
                        plugins: { legend: { display: true, position: 'top', align: 'end', labels: { usePointStyle: true, pointStyleWidth: 8, padding: 16 } } }
                    }
                })" x-init="init()" x-on:destroy="destroy()">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        {{-- Status doughnut chart --}}
        <div class="card">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Distribucion</h2>
            </div>
            <div class="p-6">
                <div class="h-64" x-data="chartComponent({
                    type: 'doughnut',
                    data: {
                        labels: ['Autorizados', 'Pendientes', 'Borradores', 'Rechazados'],
                        datasets: [{
                            data: [
                                {{ $this->documentsByStatus['authorized'] ?? 0 }},
                                {{ $this->documentsByStatus['pending'] ?? 0 }},
                                {{ $this->documentsByStatus['draft'] ?? 0 }},
                                {{ $this->documentsByStatus['rejected'] ?? 0 }}
                            ],
                            backgroundColor: ['#10b981', '#f59e0b', '#94a3b8', '#ef4444'],
                            borderWidth: 0,
                            hoverOffset: 6,
                        }]
                    }
                })" x-init="init()" x-on:destroy="destroy()">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Main content grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Recent documents --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                            <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Documentos Recientes</h2>
                    </div>
                    <a href="{{ route('panel.documents.index') }}"
                        class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 transition-colors">
                        Ver todos
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>

                @if ($this->recentDocuments->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Sin documentos aún</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-xs">Crea tu primera factura
                            para empezar a gestionar tus documentos</p>
                        <a href="{{ route('panel.invoices.create') }}" class="mt-5 btn-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Nueva Factura
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($this->recentDocuments as $document)
                            <a href="{{ route('panel.documents.show', $document) }}"
                                class="flex items-center gap-4 px-6 py-3.5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors duration-150 group">
                                @php
                                    $statusConfig = match (true) {
                                        $document->status === \App\Enums\DocumentStatus::AUTHORIZED => [
                                            'bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
                                            'text' => 'text-emerald-600 dark:text-emerald-400',
                                        ],
                                        $document->status === \App\Enums\DocumentStatus::PROCESSING ||
                                            $document->status === \App\Enums\DocumentStatus::SENT
                                            => [
                                            'bg' => 'bg-amber-100 dark:bg-amber-900/30',
                                            'text' => 'text-amber-600 dark:text-amber-400',
                                        ],
                                        $document->status === \App\Enums\DocumentStatus::REJECTED => [
                                            'bg' => 'bg-rose-100 dark:bg-rose-900/30',
                                            'text' => 'text-rose-600 dark:text-rose-400',
                                        ],
                                        default => [
                                            'bg' => 'bg-slate-100 dark:bg-slate-800',
                                            'text' => 'text-slate-500 dark:text-slate-400',
                                        ],
                                    };
                                @endphp
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-medium text-slate-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                        {{ $document->document_number ?? 'Borrador' }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                        {{ $document->customer?->business_name ?? 'Sin cliente' }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white tabular-nums">
                                        ${{ number_format($document->total, 2) }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        {{ $document->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <svg class="h-4 w-4 text-slate-300 dark:text-slate-600 shrink-0 group-hover:text-slate-400 transition-colors"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Document status breakdown --}}
            <div class="card">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Estado de Documentos</h2>
                </div>
                <div class="p-6 space-y-4">
                    @php
                        $statusList = [
                            [
                                'label' => 'Autorizados',
                                'key' => 'authorized',
                                'color' => 'bg-emerald-500',
                                'ring' => 'ring-emerald-500/20',
                            ],
                            [
                                'label' => 'Pendientes',
                                'key' => 'pending',
                                'color' => 'bg-amber-500',
                                'ring' => 'ring-amber-500/20',
                            ],
                            [
                                'label' => 'Borradores',
                                'key' => 'draft',
                                'color' => 'bg-slate-400',
                                'ring' => 'ring-slate-400/20',
                            ],
                            [
                                'label' => 'Rechazados',
                                'key' => 'rejected',
                                'color' => 'bg-rose-500',
                                'ring' => 'ring-rose-500/20',
                            ],
                        ];
                        $total = max(array_sum(array_values($this->documentsByStatus)), 1);
                    @endphp

                    {{-- Visual bar --}}
                    <div class="flex h-2.5 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                        @foreach ($statusList as $status)
                            @php $pct = ($this->documentsByStatus[$status['key']] / $total) * 100; @endphp
                            @if ($pct > 0)
                                <div class="{{ $status['color'] }} transition-all duration-700"
                                    style="width: {{ $pct }}%"></div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Legend --}}
                    <div class="space-y-3">
                        @foreach ($statusList as $status)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full {{ $status['color'] }} ring-2 {{ $status['ring'] }}"></span>
                                    <span
                                        class="text-sm text-slate-600 dark:text-slate-400">{{ $status['label'] }}</span>
                                </div>
                                <span
                                    class="text-sm font-bold text-slate-900 dark:text-white tabular-nums">{{ $this->documentsByStatus[$status['key']] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="card">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Acciones Rápidas</h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-2">
                        @php
                            $actions = [
                                [
                                    'route' => 'panel.invoices.create',
                                    'label' => 'Factura',
                                    'color' => 'primary',
                                    'icon' => 'M12 4.5v15m7.5-7.5h-15',
                                ],
                                [
                                    'route' => 'panel.customers.create',
                                    'label' => 'Cliente',
                                    'color' => 'violet',
                                    'icon' =>
                                        'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z',
                                ],
                                [
                                    'route' => 'panel.products.create',
                                    'label' => 'Producto',
                                    'color' => 'emerald',
                                    'icon' =>
                                        'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
                                ],
                                [
                                    'route' => 'panel.reports.index',
                                    'label' => 'Reportes',
                                    'color' => 'amber',
                                    'icon' =>
                                        'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                                ],
                            ];
                        @endphp

                        @foreach ($actions as $action)
                            <a href="{{ route($action['route']) }}"
                                class="flex flex-col items-center gap-2.5 rounded-xl border border-slate-200/80 p-4 hover:border-{{ $action['color'] }}-300 hover:bg-{{ $action['color'] }}-50/50 dark:border-slate-700/80 dark:hover:border-{{ $action['color'] }}-700 dark:hover:bg-{{ $action['color'] }}-900/10 transition-all duration-200 group">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-{{ $action['color'] }}-100 text-{{ $action['color'] }}-600 dark:bg-{{ $action['color'] }}-900/30 dark:text-{{ $action['color'] }}-400 group-hover:scale-110 transition-transform duration-200">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="{{ $action['icon'] }}" />
                                    </svg>
                                </div>
                                <span
                                    class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Plan usage --}}
            @php
                $tenant = auth()->user()->tenant;
                $usage = $tenant ? ($tenant->documents_this_month / max($tenant->max_documents_per_month, 1)) * 100 : 0;
            @endphp
            <div
                class="relative overflow-hidden rounded-xl bg-gradient-to-br from-slate-900 via-primary-950 to-slate-900 p-6 text-white ring-1 ring-white/[0.06]">
                {{-- Decorative glow --}}
                <div class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-primary-500/20 blur-3xl"></div>
                <div class="absolute -bottom-8 -left-8 h-24 w-24 rounded-full bg-primary-400/10 blur-2xl"></div>

                <div class="relative">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-semibold">Tu Plan</h2>
                        <span class="badge bg-white/10 text-white/80 ring-white/10 text-[11px]">
                            {{ $tenant?->plan?->name ?? 'Starter' }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-end justify-between text-sm mb-2">
                            <span class="text-slate-300">Documentos este mes</span>
                            <span class="font-bold tabular-nums">{{ $tenant?->documents_this_month ?? 0 }} <span
                                    class="text-slate-500 font-normal">/
                                    {{ $tenant?->max_documents_per_month ?? 0 }}</span></span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full {{ $usage >= 90 ? 'bg-rose-500' : ($usage >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }} transition-all duration-700"
                                style="width: {{ min($usage, 100) }}%"></div>
                        </div>
                    </div>
                    @if ($usage >= 80)
                        <a href="{{ route('panel.settings.billing') }}"
                            class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100 shadow-lg shadow-white/10 transition-all duration-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            Ampliar Plan
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
