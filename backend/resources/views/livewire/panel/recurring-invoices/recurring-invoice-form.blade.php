<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $recurringInvoiceId ? 'Editar Factura Recurrente' : 'Nueva Factura Recurrente' }}
            </h2>
        </div>
        <a href="{{ route('panel.recurring-invoices.index') }}" wire:navigate
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; Volver
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Emisor --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Datos de Emisión</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Empresa</label>
                    <select wire:model.live="company_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->business_name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sucursal</label>
                    <select wire:model.live="branch_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                    @error('branch_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Punto de Emisión</label>
                    <select wire:model="emission_point_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach ($emissionPoints as $ep)
                            <option value="{{ $ep->id }}">{{ $ep->code }}</option>
                        @endforeach
                    </select>
                    @error('emission_point_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Cliente --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cliente</h3>
            <div>
                <input type="text" wire:model.live.debounce.300ms="customerSearch" placeholder="Buscar por nombre o identificación..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('customer_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

                @if ($this->customers->isNotEmpty())
                    <div class="mt-2 rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-600 dark:bg-gray-700">
                        @foreach ($this->customers as $customer)
                            <button type="button" wire:click="$set('customer_id', {{ $customer->id }}); $set('customerSearch', '{{ addslashes($customer->name) }}')"
                                class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-600 {{ $customer_id === $customer->id ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                <span class="font-medium">{{ $customer->name }}</span>
                                <span class="text-gray-500 ml-2">{{ $customer->identification }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Programación --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Programación</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frecuencia</label>
                    <select wire:model="frequency"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="weekly">Semanal</option>
                        <option value="biweekly">Quincenal</option>
                        <option value="monthly">Mensual</option>
                        <option value="bimonthly">Bimestral</option>
                        <option value="quarterly">Trimestral</option>
                        <option value="semiannual">Semestral</option>
                        <option value="annual">Anual</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha inicio</label>
                    <input type="date" wire:model="start_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    @error('start_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha fin (opcional)</label>
                    <input type="date" wire:model="end_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max. emisiones (opcional)</label>
                    <input type="number" wire:model="max_issues" min="1" placeholder="Sin limite"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Productos / Servicios</h3>
                <button type="button" wire:click="addItem"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar
                </button>
            </div>
            @error('items') <p class="mb-2 text-sm text-red-500">{{ $message }}</p> @enderror

            <div class="space-y-3">
                @foreach ($items as $index => $item)
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-600">
                        <div class="grid grid-cols-12 gap-2 items-end">
                            <div class="col-span-12 sm:col-span-4">
                                <label class="block text-xs text-gray-500 dark:text-gray-400">Descripción</label>
                                <input type="text" wire:model="items.{{ $index }}.description"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                @error("items.{$index}.description") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <label class="block text-xs text-gray-500 dark:text-gray-400">Cantidad</label>
                                <input type="number" wire:model="items.{{ $index }}.quantity" step="0.01" min="0.01"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <label class="block text-xs text-gray-500 dark:text-gray-400">P. Unitario</label>
                                <input type="number" wire:model="items.{{ $index }}.unit_price" step="0.01" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <label class="block text-xs text-gray-500 dark:text-gray-400">IVA %</label>
                                <select wire:model="items.{{ $index }}.tax_rate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="0">0%</option>
                                    <option value="5">5%</option>
                                    <option value="12">12%</option>
                                    <option value="15">15%</option>
                                </select>
                            </div>
                            <div class="col-span-1 sm:col-span-2 flex items-end justify-end">
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Estimated total --}}
            <div class="mt-4 flex justify-end">
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total estimado por emisión:</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($estimatedTotal, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Notas --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notas</h3>
            <textarea wire:model="notes" rows="3" placeholder="Notas adicionales para la factura..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"></textarea>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('panel.recurring-invoices.index') }}" wire:navigate
                class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ $recurringInvoiceId ? 'Actualizar' : 'Crear Factura Recurrente' }}
            </button>
        </div>
    </form>
</div>
