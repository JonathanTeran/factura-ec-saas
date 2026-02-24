<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('portal.documents.index') }}" class="mb-2 inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver a documentos
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $document->document_type->label() }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $document->getDocumentNumber() }}
            </p>
        </div>
        <div class="flex gap-2">
            @if($document->ride_pdf_path)
            <a href="{{ route('portal.documents.ride', $document->id) }}"
               class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-500">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Descargar PDF
            </a>
            @endif
            @if($document->xml_authorized_path)
            <a href="{{ route('portal.documents.xml', $document->id) }}"
               class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-500">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Descargar XML
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Info principal --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Datos del documento --}}
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Datos del documento</h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Tipo</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->document_type->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Numero</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $document->getDocumentNumber() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Fecha de emision</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->issue_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                            <dd class="mt-1">
                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    {{ $document->status->label() }}
                                </span>
                            </dd>
                        </div>
                        @if($document->authorization_number)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">No. Autorizacion</dt>
                            <dd class="mt-1 break-all text-sm font-mono text-gray-900 dark:text-white">{{ $document->authorization_number }}</dd>
                        </div>
                        @endif
                        @if($document->access_key)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Clave de Acceso</dt>
                            <dd class="mt-1 break-all text-sm font-mono text-gray-900 dark:text-white">{{ $document->access_key }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Items --}}
            @if($document->items->count() > 0)
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Detalle</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descripcion</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cant.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">P. Unit.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Desc.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($document->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $item->description }}</p>
                                    @if($item->main_code)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Cod: {{ $item->main_code }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ $item->quantity }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">${{ number_format($item->discount, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Empresa emisora --}}
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Empresa emisora</h2>
                </div>
                <div class="p-4">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Razon social</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->company->business_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">RUC</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $document->company->ruc ?? '-' }}</dd>
                        </div>
                        @if($document->company->address ?? false)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Direccion</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->company->address }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Totales --}}
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Totales</h2>
                </div>
                <div class="p-4">
                    <dl class="space-y-2">
                        @if($document->subtotal_0 > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subtotal 0%</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->subtotal_0, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->subtotal_12 > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subtotal 12%</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->subtotal_12, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->subtotal_15 > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subtotal 15%</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->subtotal_15, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->subtotal_5 > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subtotal 5%</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->subtotal_5, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->subtotal_no_tax > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">No objeto de IVA</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->subtotal_no_tax, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->total_discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Descuento</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">-${{ number_format($document->total_discount, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->total_tax > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">IVA</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->total_tax, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->tip > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Propina</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($document->tip, 2) }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                            <dt class="text-base font-semibold text-gray-900 dark:text-white">TOTAL</dt>
                            <dd class="text-base font-bold text-gray-900 dark:text-white">${{ number_format($document->total, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Metodos de pago --}}
            @if($document->payment_methods && count($document->payment_methods) > 0)
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Forma de pago</h2>
                </div>
                <div class="p-4">
                    <ul class="space-y-2">
                        @foreach($document->payment_methods as $pm)
                        <li class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ $pm['description'] ?? $pm['code'] ?? 'Pago' }}</span>
                            <span class="text-gray-900 dark:text-white">${{ number_format($pm['total'] ?? $pm['value'] ?? 0, 2) }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
