<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $purchaseId ? 'Editar Compra' : 'Registrar Compra' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $purchaseId ? 'Modifica los datos de la compra' : 'Registra una factura de compra recibida' }}
            </p>
        </div>
        <a href="{{ route('panel.purchases.index') }}" class="btn-secondary">Volver</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Datos generales --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Datos del documento</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="label">Empresa</label>
                    <select wire:model.live="companyId" class="input w-full">
                        <option value="">Seleccionar...</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->commercial_name ?: $company->business_name }}</option>
                        @endforeach
                    </select>
                    @error('companyId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Proveedor</label>
                    <div class="flex gap-2">
                        <select wire:model="supplierId" class="input flex-1">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->business_name }} ({{ $supplier->identification }})</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="$toggle('showSupplierForm')" class="btn-secondary px-3" title="Nuevo proveedor">+</button>
                    </div>
                    @error('supplierId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Tipo documento</label>
                    <select wire:model="documentType" class="input w-full">
                        <option value="01">Factura</option>
                        <option value="03">Liquidacion de Compra</option>
                        <option value="41">Reembolso</option>
                    </select>
                </div>

                <div>
                    <label class="label">No. Documento</label>
                    <input type="text" wire:model="supplierDocumentNumber" class="input w-full" placeholder="001-001-000000001" />
                    @error('supplierDocumentNumber') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Autorizacion</label>
                    <input type="text" wire:model="supplierAuthorization" class="input w-full" placeholder="No. autorizacion SRI" />
                </div>

                <div>
                    <label class="label">Fecha emision</label>
                    <input type="date" wire:model="issueDate" class="input w-full" />
                    @error('issueDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- New Supplier Modal --}}
        @if($showSupplierForm)
            <div class="card p-6 space-y-4 border-2 border-primary-500">
                <h3 class="text-base font-semibold">Nuevo Proveedor</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Tipo identificacion</label>
                        <select wire:model="newSupplierType" class="input w-full">
                            @foreach($identificationTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Identificacion</label>
                        <input type="text" wire:model="newSupplierIdentification" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Razon Social</label>
                        <input type="text" wire:model="newSupplierName" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input type="email" wire:model="newSupplierEmail" class="input w-full" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="createSupplier" class="btn-primary">Crear proveedor</button>
                    <button type="button" wire:click="$set('showSupplierForm', false)" class="btn-ghost">Cancelar</button>
                </div>
            </div>
        @endif

        {{-- Items --}}
        <div class="card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Items</h2>
                <button type="button" wire:click="addItem" class="btn-secondary text-sm">+ Agregar item</button>
            </div>

            @foreach($items as $index => $item)
                <div class="grid grid-cols-12 gap-2 items-end border-b border-slate-100 pb-4 dark:border-slate-700">
                    <div class="col-span-12 sm:col-span-4">
                        <label class="label text-xs">Descripcion</label>
                        <input type="text" wire:model="items.{{ $index }}.description" class="input w-full text-sm" />
                    </div>
                    <div class="col-span-4 sm:col-span-2">
                        <label class="label text-xs">Cantidad</label>
                        <input type="number" step="0.01" wire:model="items.{{ $index }}.quantity" class="input w-full text-sm" />
                    </div>
                    <div class="col-span-4 sm:col-span-2">
                        <label class="label text-xs">P. Unitario</label>
                        <input type="number" step="0.01" wire:model="items.{{ $index }}.unit_price" class="input w-full text-sm" />
                    </div>
                    <div class="col-span-4 sm:col-span-1">
                        <label class="label text-xs">IVA %</label>
                        <input type="number" step="1" wire:model="items.{{ $index }}.tax_rate" class="input w-full text-sm" />
                    </div>
                    <div class="col-span-8 sm:col-span-2">
                        <label class="label text-xs">Total</label>
                        @php
                            $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                            $tax = $subtotal * ($item['tax_rate'] ?? 0) / 100;
                        @endphp
                        <p class="py-2 px-3 text-sm font-semibold tabular-nums">${{ number_format($subtotal + $tax, 2) }}</p>
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
        </div>

        {{-- Notes --}}
        <div class="card p-6">
            <label class="label">Notas</label>
            <textarea wire:model="notes" class="input w-full" rows="2" placeholder="Notas adicionales..."></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('panel.purchases.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $purchaseId ? 'Actualizar compra' : 'Registrar compra' }}
            </button>
        </div>
    </form>
</div>
