<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Documentos
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Gestiona todos tus comprobantes electrónicos
            </p>
        </div>
        <a href="{{ route('panel.documents.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo documento
        </a>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 stagger-children">
        {{-- Today --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600 shadow-lg shadow-primary-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['today'] }}">{{ $stats['today'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Hoy</p>
                </div>
            </div>
        </div>

        {{-- This month --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-indigo-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-lg shadow-indigo-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['thisMonth'] }}">{{ $stats['thisMonth'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Este mes</p>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-amber-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-500 shadow-lg shadow-amber-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['pending'] }}">{{ $stats['pending'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pendientes</p>
                </div>
            </div>
        </div>

        {{-- Authorized --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg shadow-emerald-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl" data-counter="{{ $stats['authorized'] }}">{{ $stats['authorized'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Autorizados</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                {{-- Search --}}
                <div class="flex-1">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" id="search"
                               placeholder="Buscar por clave de acceso, secuencial o cliente..."
                               class="form-input !pl-11">
                    </div>
                </div>

                {{-- Status filter --}}
                <div class="w-full sm:w-40">
                    <label for="status" class="form-label">Estado</label>
                    <select wire:model.live="status" id="status" class="form-input">
                        <option value="">Todos</option>
                        @foreach($documentStatuses as $documentStatus)
                            <option value="{{ $documentStatus->value }}">{{ $documentStatus->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type filter --}}
                <div class="w-full sm:w-44">
                    <label for="type" class="form-label">Tipo</label>
                    <select wire:model.live="type" id="type" class="form-input">
                        <option value="">Todos</option>
                        @foreach($documentTypes as $documentType)
                            <option value="{{ $documentType->value }}">{{ $documentType->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date range --}}
                <div class="flex gap-3">
                    <div class="w-full sm:w-36">
                        <label for="dateFrom" class="form-label">Desde</label>
                        <input wire:model.live="dateFrom" type="date" id="dateFrom" class="form-input">
                    </div>
                    <div class="w-full sm:w-36">
                        <label for="dateTo" class="form-label">Hasta</label>
                        <input wire:model.live="dateTo" type="date" id="dateTo" class="form-input">
                    </div>
                </div>

                {{-- Clear filters --}}
                @if($search || $status || $type || $dateFrom || $dateTo)
                    <button wire:click="clearFilters" type="button" class="btn-ghost btn-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Bulk Actions --}}
    @if(count($selectedDocuments) > 0)
        <div class="flex items-center justify-between rounded-xl bg-primary-50 px-5 py-3.5 ring-1 ring-primary-200 dark:bg-primary-950/30 dark:ring-primary-800">
            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">
                {{ count($selectedDocuments) }} documento(s) seleccionado(s)
            </p>
            <div class="flex items-center gap-2">
                <button wire:click="bulkDownload" type="button" class="btn-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Descargar
                </button>
            </div>
        </div>
    @endif

    {{-- Documents Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12 px-4">
                            <input wire:model.live="selectAll" type="checkbox"
                                   class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                        </th>
                        <th class="px-4">
                            <button wire:click="sortBy('document_type')" class="group inline-flex items-center gap-1.5">
                                Tipo
                                @if($sortField === 'document_type')
                                    <svg class="h-3.5 w-3.5 text-primary-500 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4">
                            <button wire:click="sortBy('sequential')" class="group inline-flex items-center gap-1.5">
                                Secuencial
                                @if($sortField === 'sequential')
                                    <svg class="h-3.5 w-3.5 text-primary-500 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4">Cliente</th>
                        <th class="px-4">
                            <button wire:click="sortBy('issue_date')" class="group inline-flex items-center gap-1.5">
                                Fecha
                                @if($sortField === 'issue_date')
                                    <svg class="h-3.5 w-3.5 text-primary-500 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 text-right">
                            <button wire:click="sortBy('total')" class="group inline-flex items-center gap-1.5">
                                Total
                                @if($sortField === 'total')
                                    <svg class="h-3.5 w-3.5 text-primary-500 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4">Estado</th>
                        <th class="relative px-4">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr class="group" wire:key="document-{{ $document->id }}">
                            <td class="whitespace-nowrap px-4">
                                <input wire:model.live="selectedDocuments" type="checkbox" value="{{ $document->id }}"
                                       class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                            </td>
                            <td class="whitespace-nowrap px-4">
                                <div class="flex items-center gap-3">
                                    @php
                                        $typeColors = match($document->document_type->value ?? $document->document_type) {
                                            '01' => 'bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400',
                                            '04' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
                                            '05' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
                                            '06' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400',
                                            '07' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
                                            default => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                                        };
                                    @endphp
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $typeColors }}">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $document->document_type->label() ?? 'Documento' }}
                                        </p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500">
                                            {{ $document->emissionPoint?->code ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4">
                                <div>
                                    <p class="font-mono text-sm font-medium tabular-nums text-slate-900 dark:text-white">
                                        {{ $document->sequential }}
                                    </p>
                                    @if($document->access_key)
                                        <p class="font-mono text-xs text-slate-400 dark:text-slate-500 truncate max-w-[120px]" title="{{ $document->access_key }}">
                                            {{ Str::limit($document->access_key, 12) }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4">
                                <div class="max-w-[200px]">
                                    <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $document->customer?->business_name ?? 'Consumidor Final' }}
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 tabular-nums">
                                        {{ $document->customer?->identification ?? '9999999999999' }}
                                    </p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4">
                                <p class="text-sm text-slate-900 dark:text-white">
                                    {{ $document->issue_date?->format('d/m/Y') ?? $document->created_at->format('d/m/Y') }}
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ $document->created_at->format('H:i') }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-4 text-right">
                                <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                                    ${{ number_format($document->total, 2) }}
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 tabular-nums">
                                    IVA: ${{ number_format($document->tax_total ?? 0, 2) }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-4">
                                @php
                                    $statusValue = $document->status->value ?? $document->status;
                                    $statusClass = match($statusValue) {
                                        'draft' => 'doc-status-draft',
                                        'pending' => 'doc-status-pending',
                                        'sent' => 'badge-primary',
                                        'authorized' => 'doc-status-authorized',
                                        'rejected' => 'doc-status-rejected',
                                        'voided' => 'doc-status-voided',
                                        default => 'badge-gray',
                                    };
                                    $dotClass = match($statusValue) {
                                        'draft' => 'bg-slate-400',
                                        'pending' => 'bg-amber-500',
                                        'sent' => 'bg-primary-500',
                                        'authorized' => 'bg-emerald-500',
                                        'rejected' => 'bg-rose-500',
                                        'voided' => 'bg-purple-500',
                                        default => 'bg-slate-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    <span class="badge-dot {{ $dotClass }}"></span>
                                    {{ $document->status->label() ?? ucfirst($statusValue) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100" x-data="{ open: false }">
                                    {{-- Quick actions for authorized documents --}}
                                    @if($document->status->value === 'authorized' || $document->status === 'authorized')
                                        <button wire:click="downloadPdf({{ $document->id }})" type="button"
                                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                                title="Descargar PDF">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </button>
                                        <button wire:click="sendEmail({{ $document->id }})" type="button"
                                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                                title="Enviar por correo">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- More options --}}
                                    <div class="relative">
                                        <button @click="open = !open" type="button"
                                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.away="open = false" x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="dropdown-menu absolute right-0 z-10 mt-2 w-48 origin-top-right p-1.5">
                                            <a href="{{ route('panel.documents.show', $document) }}" class="dropdown-item">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Ver detalle
                                            </a>
                                            @if($document->status->value === 'authorized' || $document->status === 'authorized')
                                                <button wire:click="downloadXml({{ $document->id }})" @click="open = false" class="dropdown-item w-full">
                                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                                                    </svg>
                                                    Descargar XML
                                                </button>
                                            @endif
                                            @if($document->status->value === 'draft' || $document->status === 'draft')
                                                <a href="{{ route('panel.documents.edit', $document) }}" class="dropdown-item">
                                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                    </svg>
                                                    Editar
                                                </a>
                                            @endif
                                            <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>
                                            <button type="button" class="dropdown-item w-full">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6" />
                                                </svg>
                                                Duplicar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-4">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                        No hay documentos
                                    </h3>
                                    <p class="mt-1.5 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                                        @if($search || $status || $type || $dateFrom || $dateTo)
                                            No se encontraron documentos con los filtros aplicados.
                                        @else
                                            Comienza creando tu primer documento electrónico.
                                        @endif
                                    </p>
                                    @if(!$search && !$status && !$type && !$dateFrom && !$dateTo)
                                        <a href="{{ route('panel.documents.create') }}" class="btn-primary mt-5">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Crear documento
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($documents->hasPages())
            <div class="card-footer">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
