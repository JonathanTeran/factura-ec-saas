<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $quoteId ? 'Editar Cotización' : 'Nueva Cotización' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $quoteId ? 'Modifica los datos de la cotización' : 'Crea una proforma o cotización para un cliente' }}
            </p>
        </div>
        <a href="{{ route('panel.quotes.index') }}" class="btn-secondary">Volver</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Datos generales --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Datos generales</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="label">Empresa</label>
                    <select wire:model.live="companyId" class="input w-full">
                        <option value="">Seleccionar...</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name ?: $company->business_name }}</option>
                        @endforeach
                    </select>
                    @error('companyId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Cliente</label>
                    <select wire:model="customerId" class="input w-full">
                        <option value="">Seleccionar...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->identification }})</option>
                        @endforeach
                    </select>
                    @error('customerId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Fecha de emisión</label>
                    <input type="date" wire:model="issueDate" class="input w-full" />
                    @error('issueDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Fecha de vencimiento</label>
                    <input type="date" wire:model="expiryDate" class="input w-full" />
                    @error('expiryDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label">Condiciones de pago</label>
                    <input type="text" wire:model="paymentTerms" class="input w-full" placeholder="Ej: 50% anticipo, 50% contra entrega" />
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Items</h2>
                <button type="button" wire:click="addItem" class="btn-secondary text-sm">+ Agregar item</button>
            </div>

            @foreach($items as $index => $item)
                <div class="grid grid-cols-12 gap-2 items-end border-b border-slate-100 pb-4 dark:border-slate-700">
                    <div class="col-span-12 sm:col-span-4">
                        <label class="label text-xs">Descripción</label>
                        <input type="text" wire:model="items.{{ $index }}.description" class="input w-full text-sm" placeholder="Descripción del producto/servicio" />
                        @error("items.{$index}.description") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-span-4 sm:col-span-2">
                        <label class="label text-xs">Cantidad</label>
                        <input type="number" step="0.01" wire:model.live="items.{{ $index }}.quantity" class="input w-full text-sm" />
                        @error("items.{$index}.quantity") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-span-4 sm:col-span-2">
                        <label class="label text-xs">P. Unitario</label>
                        <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="input w-full text-sm" />
                    </div>
                    <div class="col-span-4 sm:col-span-1">
                        <label class="label text-xs">Desc $</label>
                        <input type="number" step="0.01" wire:model.live="items.{{ $index }}.discount" class="input w-full text-sm" />
                    </div>
                    <div class="col-span-4 sm:col-span-1">
                        <label class="label text-xs">IVA %</label>
                        <select wire:model.live="items.{{ $index }}.tax_rate" class="input w-full text-sm">
                            <option value="0">0%</option>
                            <option value="5">5%</option>
                            <option value="15">15%</option>
                        </select>
                    </div>
                    <div class="col-span-8 sm:col-span-1">
                        <label class="label text-xs">Total</label>
                        @php
                            $sub = (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0);
                            $tax = $sub * (($item['tax_rate'] ?? 0) / 100);
                        @endphp
                        <p class="py-2 px-1 text-sm font-semibold tabular-nums">${{ number_format($sub + $tax, 2) }}</p>
                    </div>
                    <div class="col-span-4 sm:col-span-1 text-right">
                        @if(count($items) > 1)
                            <button type="button" wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700 p-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Totals --}}
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Subtotal:</span>
                        <span class="tabular-nums">${{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>IVA:</span>
                        <span class="tabular-nums">${{ number_format($this->totalTax, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold text-slate-900 dark:border-slate-600 dark:text-white">
                        <span>Total:</span>
                        <span class="tabular-nums">${{ number_format($this->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card p-6">
            <label class="label">Notas</label>
            <textarea wire:model="notes" class="input w-full" rows="3" placeholder="Observaciones, términos y condiciones..."></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('panel.quotes.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $quoteId ? 'Actualizar cotización' : 'Guardar cotización' }}
            </button>
        </div>
    </form>
</div>
