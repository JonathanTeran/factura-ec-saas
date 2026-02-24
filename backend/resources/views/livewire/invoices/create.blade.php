<div x-data="{ activeProductSearch: null }">
    @if(session('error'))
    <div class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900">
        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
    </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nueva Factura</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crea una nueva factura electrónica</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Company & Emission Point -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Emisor</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Empresa</label>
                        <select wire:model.live="company_id"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }} ({{ $company->ruc }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Punto de Emisión</label>
                        <select wire:model="emission_point_id"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                            @foreach($emissionPoints as $ep)
                            <option value="{{ $ep['id'] }}">{{ $ep['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Customer -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Cliente</h3>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="customer_search"
                        wire:keyup="searchCustomers($event.target.value)"
                        type="text"
                        class="block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                        placeholder="Buscar cliente por nombre o RUC/CI...">

                    @if(count($searchedCustomers) > 0)
                    <div class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg ring-1 ring-black/5 dark:bg-gray-700">
                        <ul class="max-h-60 overflow-auto py-1">
                            @foreach($searchedCustomers as $customer)
                            <li>
                                <button wire:click="selectCustomer({{ $customer['id'] }})" type="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">
                                    <span class="font-medium">{{ $customer['name'] }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $customer['identification_number'] }}</span>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @error('customer_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="mt-3 flex items-center gap-2">
                    <a href="{{ route('tenant.customers.create') }}" target="_blank"
                        class="text-sm text-primary-600 hover:text-primary-500">
                        + Nuevo cliente
                    </a>
                </div>
            </div>

            <!-- Items -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Detalle de la Factura</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($items as $index => $item)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                            x-data="{ showProductSearch: false }">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Item {{ $index + 1 }}</span>
                                @if(count($items) > 1)
                                <button wire:click="removeItem({{ $index }})" type="button"
                                    class="text-red-600 hover:text-red-500">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                <!-- Product Search / Code -->
                                <div class="col-span-12 sm:col-span-3">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Código</label>
                                    <div class="relative">
                                        <input wire:model.live="items.{{ $index }}.main_code"
                                            @focus="showProductSearch = true; $wire.searchProducts($event.target.value)"
                                            @input="$wire.searchProducts($event.target.value)"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600"
                                            placeholder="Código">

                                        <div x-show="showProductSearch && $wire.searchedProducts.length > 0" @click.away="showProductSearch = false"
                                            class="absolute z-20 mt-1 w-64 rounded-md bg-white shadow-lg ring-1 ring-black/5 dark:bg-gray-700">
                                            <ul class="max-h-48 overflow-auto py-1">
                                                @foreach($searchedProducts as $product)
                                                <li>
                                                    <button wire:click="selectProduct({{ $index }}, {{ $product['id'] }})"
                                                        @click="showProductSearch = false"
                                                        type="button"
                                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ $product['code'] }}</span>
                                                        <span class="block truncate text-gray-500 dark:text-gray-400">{{ $product['name'] }}</span>
                                                        <span class="text-primary-600">${{ number_format($product['unit_price'], 2) }}</span>
                                                    </button>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Descripción</label>
                                    <input wire:model="items.{{ $index }}.description"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600"
                                        placeholder="Descripción del producto">
                                </div>

                                <!-- Quantity -->
                                <div class="col-span-4 sm:col-span-1">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Cant.</label>
                                    <input wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                                        wire:change="calculateItemTotals({{ $index }})"
                                        type="number" step="0.01" min="0.000001"
                                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600">
                                </div>

                                <!-- Unit Price -->
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">P. Unit.</label>
                                    <input wire:model.live.debounce.500ms="items.{{ $index }}.unit_price"
                                        wire:change="calculateItemTotals({{ $index }})"
                                        type="number" step="0.01" min="0"
                                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600">
                                </div>

                                <!-- Subtotal -->
                                <div class="col-span-4 sm:col-span-1">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Subtotal</label>
                                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        ${{ number_format($item['subtotal'] ?? 0, 2) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Tax Rate -->
                            <div class="mt-3 flex items-center gap-4">
                                <span class="text-xs text-gray-500 dark:text-gray-400">IVA:</span>
                                <div class="flex gap-2">
                                    <label class="flex items-center">
                                        <input wire:model.live="items.{{ $index }}.tax_rate"
                                            wire:change="calculateItemTotals({{ $index }})"
                                            type="radio" value="0" class="size-4 text-primary-600">
                                        <span class="ml-1 text-xs text-gray-600 dark:text-gray-300">0%</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="items.{{ $index }}.tax_rate"
                                            wire:change="calculateItemTotals({{ $index }})"
                                            type="radio" value="12" class="size-4 text-primary-600">
                                        <span class="ml-1 text-xs text-gray-600 dark:text-gray-300">12%</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input wire:model.live="items.{{ $index }}.tax_rate"
                                            wire:change="calculateItemTotals({{ $index }})"
                                            type="radio" value="15" class="size-4 text-primary-600">
                                        <span class="ml-1 text-xs text-gray-600 dark:text-gray-300">15%</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button wire:click="addItem" type="button"
                        class="mt-4 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600">
                        <svg class="-ml-0.5 mr-1.5 size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Agregar Item
                    </button>
                </div>
            </div>

            <!-- Payment -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Forma de Pago</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Método de Pago</label>
                        <select wire:model="payment_method"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                            @foreach($paymentMethods as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plazo (días)</label>
                        <input wire:model="payment_term" type="number" min="0"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar - Totals -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-6">
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Resumen</h3>

                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal 0%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($subtotal_0, 2) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal 12%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($subtotal_12, 2) }}</dd>
                        </div>
                        @if($subtotal_15 > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal 15%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($subtotal_15, 2) }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">IVA 12%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($tax_12, 2) }}</dd>
                        </div>
                        @if($tax_15 > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">IVA 15%</dt>
                            <dd class="text-gray-900 dark:text-white">${{ number_format($tax_15, 2) }}</dd>
                        </div>
                        @endif
                        @if($discount > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Descuento</dt>
                            <dd class="text-red-600">-${{ number_format($discount, 2) }}</dd>
                        </div>
                        @endif
                        <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                            <div class="flex justify-between">
                                <dt class="text-lg font-semibold text-gray-900 dark:text-white">Total</dt>
                                <dd class="text-lg font-bold text-primary-600">${{ number_format($total, 2) }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="space-y-3">
                    <button wire:click="saveAndSend" type="button"
                        class="w-full rounded-md bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        <svg class="-ml-0.5 mr-1.5 inline-block size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                        Guardar y Enviar al SRI
                    </button>

                    <button wire:click="saveAsDraft" type="button"
                        class="w-full rounded-md bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                        Guardar como Borrador
                    </button>

                    <a href="{{ route('tenant.documents.index') }}"
                        class="block w-full rounded-md px-4 py-3 text-center text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
