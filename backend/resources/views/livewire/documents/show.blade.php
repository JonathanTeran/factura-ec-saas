<div>
    <!-- Flash messages -->
    @if(session('success'))
    <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900">
        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
    </div>
    @endif

    <!-- Header -->
    <div class="mb-6 lg:flex lg:items-center lg:justify-between">
        <div class="min-w-0 flex-1">
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <a href="{{ route('tenant.documents.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Documentos
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="size-5 shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $document->document_number }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $document->document_type->label() }} {{ $document->document_number }}
            </h1>
            <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="mr-1.5 size-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    {{ $document->issue_date?->format('d/m/Y H:i') }}
                </div>
                <div class="mt-2 flex items-center text-sm">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        @if($document->status->color() === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($document->status->color() === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif($document->status->color() === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                        @endif">
                        {{ $document->status->label() }}
                    </span>
                </div>
            </div>
        </div>
        <div class="mt-5 flex lg:ml-4 lg:mt-0">
            @if($document->status->isEditable())
            <button wire:click="sendToSRI" type="button"
                class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                <svg class="-ml-0.5 mr-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                Enviar al SRI
            </button>
            @endif

            @if($document->ride_path)
            <a href="#" class="ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                <svg class="-ml-0.5 mr-1.5 size-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Descargar RIDE
            </a>
            @endif

            @if($document->status->value === 'authorized')
            <button wire:click="resendEmail" type="button"
                class="ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                <svg class="-ml-0.5 mr-1.5 size-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                Reenviar Email
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Info -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Información del Cliente</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre/Razón Social</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->customer?->name ?? 'Consumidor Final' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Identificación</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->customer?->identification_number ?? '9999999999999' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->customer?->email ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dirección</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->customer?->address ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Items -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Detalle</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Producto</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Cantidad</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">P. Unitario</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @foreach($document->items as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $item->description }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->main_code }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                    {{ number_format($item->quantity, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                    ${{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Totals -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Totales</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal 0%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($document->subtotal_0, 2) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal 12%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($document->subtotal_12, 2) }}</dd>
                        </div>
                        @if($document->subtotal_15 > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal 15%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($document->subtotal_15, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->total_tax > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">IVA</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($document->total_tax, 2) }}</dd>
                        </div>
                        @endif
                        @if($document->total_discount > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Descuento</dt>
                            <dd class="text-red-600">-${{ number_format($document->total_discount, 2) }}</dd>
                        </div>
                        @endif
                        <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                            <div class="flex justify-between text-base font-semibold">
                                <dt class="text-gray-900 dark:text-white">Total</dt>
                                <dd class="text-gray-900 dark:text-white">${{ number_format($document->total, 2) }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- SRI Info -->
            @if($document->authorization_number)
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Información SRI</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Clave de Acceso</dt>
                            <dd class="mt-1 break-all text-xs text-gray-900 dark:text-white">{{ $document->access_key }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">No. Autorización</dt>
                            <dd class="mt-1 break-all text-xs text-gray-900 dark:text-white">{{ $document->authorization_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha Autorización</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $document->authorization_date?->format('d/m/Y H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            @endif

            <!-- SRI Messages -->
            @if(isset($document->sri_response['messages']) && count($document->sri_response['messages']) > 0)
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Mensajes SRI</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <ul class="space-y-2">
                        @foreach($document->sri_response['messages'] as $message)
                        <li class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">{{ $message['identificador'] ?? '' }}:</span>
                            {{ $message['mensaje'] ?? $message }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
