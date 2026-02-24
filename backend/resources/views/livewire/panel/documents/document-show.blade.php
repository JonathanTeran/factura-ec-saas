<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <a href="{{ route('panel.documents.index') }}" class="btn-ghost btn-icon shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        {{ $document->document_type->label() }}
                    </h1>
                    @php
                        $statusValue = $document->status->value ?? $document->status;
                        $statusClass = match ($statusValue) {
                            'draft' => 'doc-status-draft',
                            'pending' => 'doc-status-pending',
                            'sent' => 'badge-primary',
                            'authorized' => 'doc-status-authorized',
                            'rejected' => 'doc-status-rejected',
                            'voided' => 'doc-status-voided',
                            default => 'badge-gray',
                        };
                        $dotClass = match ($statusValue) {
                            'draft' => 'bg-slate-400',
                            'pending' => 'bg-amber-500',
                            'sent' => 'bg-primary-500',
                            'authorized' => 'bg-emerald-500',
                            'rejected' => 'bg-rose-500',
                            'voided' => 'bg-purple-500',
                            default => 'bg-slate-400',
                        };
                    @endphp
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium {{ $statusClass }}">
                        <span class="badge-dot {{ $dotClass }}"></span>
                        {{ $document->status->label() }}
                    </span>
                </div>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                    Secuencial: <span
                        class="font-mono font-medium tabular-nums text-slate-700 dark:text-slate-300">{{ $document->sequential }}</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($document->status === \App\Enums\DocumentStatus::AUTHORIZED)
                <button wire:click="downloadPdf" type="button" class="btn-outline">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    PDF
                </button>
                <button wire:click="downloadXml" type="button" class="btn-outline">
                    <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                    </svg>
                    XML
                </button>
                <button wire:click="sendEmail" type="button" class="btn-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                    Enviar
                </button>
            @elseif($document->status === \App\Enums\DocumentStatus::DRAFT)
                <a href="{{ route('panel.documents.edit', $document) }}" class="btn-outline">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Editar
                </a>
                <button wire:click="resendToSri" type="button" class="btn-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Emitir
                </button>
            @elseif($document->status === \App\Enums\DocumentStatus::REJECTED)
                <button wire:click="resendToSri" type="button"
                    class="btn bg-amber-500 text-white hover:bg-amber-600 hover:shadow-lg hover:shadow-amber-500/25">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Reintentar
                </button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Customer Info --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        Datos del Cliente
                    </h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Razón Social</p>
                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                                {{ $document->customer?->business_name ?? 'Consumidor Final' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Identificación</p>
                            <p class="mt-1 font-mono text-sm font-medium tabular-nums text-slate-900 dark:text-white">
                                {{ $document->customer?->identification ?? '9999999999999' }}
                            </p>
                        </div>
                        @if ($document->customer?->email)
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    Correo</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $document->customer->email }}</p>
                            </div>
                        @endif
                        @if ($document->customer?->phone)
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    Teléfono</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $document->customer->phone }}</p>
                            </div>
                        @endif
                        @if ($document->customer?->address)
                            <div class="sm:col-span-2">
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    Dirección</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $document->customer->address }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Document Items --}}
            <div class="card overflow-hidden">
                <div class="card-header">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Detalle de Items
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="px-5">#</th>
                                <th class="px-5">Descripción</th>
                                <th class="px-5 text-center">Cant.</th>
                                <th class="px-5 text-right">P. Unit.</th>
                                <th class="px-5 text-right">Desc.</th>
                                <th class="px-5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($document->items as $index => $item)
                                <tr>
                                    <td class="whitespace-nowrap px-5 text-slate-400 dark:text-slate-500">
                                        {{ $index + 1 }}</td>
                                    <td class="px-5">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $item->description ?? $item->product?->name }}
                                        </p>
                                        @if ($item->product?->code)
                                            <p
                                                class="font-mono text-xs text-slate-400 dark:text-slate-500 tabular-nums">
                                                {{ $item->product->code }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 text-center tabular-nums">
                                        {{ number_format($item->quantity, 2) }}</td>
                                    <td class="whitespace-nowrap px-5 text-right tabular-nums">
                                        ${{ number_format($item->unit_price, 2) }}</td>
                                    <td
                                        class="whitespace-nowrap px-5 text-right text-slate-500 dark:text-slate-400 tabular-nums">
                                        @if ($item->discount > 0)
                                            ${{ number_format($item->discount, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-5 text-right font-medium tabular-nums text-slate-900 dark:text-white">
                                        ${{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No hay items en este documento
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="card-footer">
                    <div class="flex justify-end">
                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Subtotal 12%</span>
                                <span
                                    class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($document->subtotal_12 ?? 0, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Subtotal 0%</span>
                                <span
                                    class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($document->subtotal_0 ?? 0, 2) }}</span>
                            </div>
                            @if ($document->subtotal_exempt > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">No objeto de IVA</span>
                                    <span
                                        class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($document->subtotal_exempt, 2) }}</span>
                                </div>
                            @endif
                            @if ($document->discount_total > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Descuentos</span>
                                    <span
                                        class="font-medium tabular-nums text-rose-600 dark:text-rose-400">-${{ number_format($document->discount_total, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">IVA 12%</span>
                                <span
                                    class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($document->tax_total ?? 0, 2) }}</span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-base font-semibold text-slate-900 dark:text-white">Total</span>
                                    <span
                                        class="text-xl font-bold tabular-nums text-slate-900 dark:text-white">${{ number_format($document->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Methods --}}
            @if ($document->payments && $document->payments->count() > 0)
                <div class="card">
                    <div class="card-body">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            Formas de Pago
                        </h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($document->payments as $payment)
                                <div
                                    class="flex items-center justify-between rounded-xl bg-slate-50 p-3.5 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:ring-slate-700/50">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:bg-slate-800 dark:ring-white/[0.06]">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                                {{ $payment->payment_method->label() ?? $payment->payment_method }}
                                            </p>
                                            @if ($payment->term)
                                                <p class="text-xs text-slate-500 dark:text-slate-400">Plazo:
                                                    {{ $payment->term }} días</p>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                                        ${{ number_format($payment->amount, 2) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Document Info --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Información del Documento</h3>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt
                                class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Fecha de Emisión</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                                {{ $document->issue_date?->format('d/m/Y') ?? $document->created_at->format('d/m/Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Punto de Emisión</dt>
                            <dd class="mt-1 text-sm font-medium tabular-nums text-slate-900 dark:text-white">
                                {{ $document->emissionPoint?->code ?? '-' }}
                            </dd>
                        </div>
                        @if ($document->access_key)
                            <div>
                                <dt
                                    class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    Clave de Acceso</dt>
                                <dd class="mt-1">
                                    <div class="flex items-center gap-2">
                                        <code
                                            class="block break-all rounded-lg bg-slate-50 px-2.5 py-1.5 font-mono text-xs tabular-nums text-slate-700 ring-1 ring-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                                            {{ $document->access_key }}
                                        </code>
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $document->access_key }}')"
                                            class="shrink-0 rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                                            title="Copiar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                            </svg>
                                        </button>
                                    </div>
                                </dd>
                            </div>
                        @endif
                        @if ($document->authorization_date)
                            <div>
                                <dt
                                    class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    Fecha Autorización</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $document->authorization_date->format('d/m/Y H:i:s') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- SRI Status --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Estado SRI</h3>
                    <div class="mt-4">
                        <div class="flex items-center gap-3">
                            @if ($document->status === \App\Enums\DocumentStatus::AUTHORIZED)
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-900/50">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Autorizado
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Documento válido</p>
                                </div>
                            @elseif($document->status === \App\Enums\DocumentStatus::REJECTED)
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100 dark:bg-rose-950/50 dark:text-rose-400 dark:ring-rose-900/50">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-rose-600 dark:text-rose-400">Rechazado</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Error en validación</p>
                                </div>
                            @elseif(
                                $document->status === \App\Enums\DocumentStatus::PROCESSING ||
                                    $document->status === \App\Enums\DocumentStatus::SENT)
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100 dark:bg-amber-950/50 dark:text-amber-400 dark:ring-amber-900/50">
                                    <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-amber-600 dark:text-amber-400">Procesando</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Esperando respuesta</p>
                                </div>
                            @else
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-500 ring-1 ring-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">Borrador</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No enviado</p>
                                </div>
                            @endif
                        </div>

                        @if ($document->sri_message)
                            <div
                                class="mt-4 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:ring-slate-700/50">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Mensaje SRI:</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $document->sri_message }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Activity Log --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Actividad</h3>
                    <div class="mt-4">
                        <ol class="relative border-l border-slate-200 dark:border-slate-700">
                            <li class="mb-6 ml-4">
                                <div
                                    class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-primary-500 dark:border-slate-900">
                                </div>
                                <time
                                    class="text-xs text-slate-400 dark:text-slate-500">{{ $document->created_at->format('d/m/Y H:i') }}</time>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Documento creado</p>
                            </li>
                            @if ($document->signed_at)
                                <li class="mb-6 ml-4">
                                    <div
                                        class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-indigo-500 dark:border-slate-900">
                                    </div>
                                    <time
                                        class="text-xs text-slate-400 dark:text-slate-500">{{ $document->signed_at->format('d/m/Y H:i') }}</time>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">Documento firmado</p>
                                </li>
                            @endif
                            @if ($document->sent_at)
                                <li class="mb-6 ml-4">
                                    <div
                                        class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-amber-500 dark:border-slate-900">
                                    </div>
                                    <time
                                        class="text-xs text-slate-400 dark:text-slate-500">{{ $document->sent_at->format('d/m/Y H:i') }}</time>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">Enviado al SRI</p>
                                </li>
                            @endif
                            @if ($document->authorization_date)
                                <li class="ml-4">
                                    <div
                                        class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-900">
                                    </div>
                                    <time
                                        class="text-xs text-slate-400 dark:text-slate-500">{{ $document->authorization_date->format('d/m/Y H:i') }}</time>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">Autorizado por SRI
                                    </p>
                                </li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
