<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $documentId ? 'Editar Documento Recibido' : 'Registrar Documento Recibido' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Registra facturas y comprobantes que has recibido de terceros
            </p>
        </div>
        <a href="{{ route('panel.received-documents.index') }}" class="btn-secondary">Volver</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Identificación del documento --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Identificación del documento</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="label">Empresa receptora</label>
                    <select wire:model="companyId" class="input w-full">
                        <option value="">Seleccionar...</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name ?: $company->business_name }}</option>
                        @endforeach
                    </select>
                    @error('companyId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Tipo de documento</label>
                    <select wire:model="documentType" class="input w-full">
                        <option value="01">Factura</option>
                        <option value="03">Liquidación de compra</option>
                        <option value="04">Nota de crédito</option>
                        <option value="05">Nota de débito</option>
                        <option value="06">Guía de remisión</option>
                        <option value="07">Comprobante de retención</option>
                    </select>
                </div>

                <div>
                    <label class="label">Fecha de emisión</label>
                    <input type="date" wire:model="issueDate" class="input w-full" />
                    @error('issueDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Clave de acceso (49 dígitos)</label>
                    <input type="text" wire:model="accessKey" class="input w-full font-mono text-sm" maxlength="49" placeholder="0000000000000000000000000000000000000000000000000" />
                </div>

                <div>
                    <label class="label">No. autorización SRI</label>
                    <input type="text" wire:model="authorizationNumber" class="input w-full" />
                </div>

                <div>
                    <label class="label">Fecha de autorización</label>
                    <input type="date" wire:model="authorizationDate" class="input w-full" />
                </div>
            </div>
        </div>

        {{-- Datos del emisor --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Datos del emisor</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">RUC del emisor</label>
                    <input type="text" wire:model="issuerRuc" class="input w-full font-mono" maxlength="13" placeholder="1234567890001" />
                    @error('issuerRuc') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Razón social del emisor</label>
                    <input type="text" wire:model="issuerName" class="input w-full" placeholder="Nombre o razón social" />
                    @error('issuerName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Valores --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valores del documento</h2>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div>
                    <label class="label text-xs">Subtotal 0%</label>
                    <input type="number" step="0.01" wire:model.live="subtotal0" class="input w-full text-sm" />
                </div>
                <div>
                    <label class="label text-xs">Subtotal 5%</label>
                    <input type="number" step="0.01" wire:model.live="subtotal5" class="input w-full text-sm" />
                </div>
                <div>
                    <label class="label text-xs">Subtotal 12%</label>
                    <input type="number" step="0.01" wire:model.live="subtotal12" class="input w-full text-sm" />
                </div>
                <div>
                    <label class="label text-xs">Subtotal 15%</label>
                    <input type="number" step="0.01" wire:model.live="subtotal15" class="input w-full text-sm" />
                </div>
                <div>
                    <label class="label text-xs">Subtotal sin IVA</label>
                    <input type="number" step="0.01" wire:model.live="subtotalNoTax" class="input w-full text-sm" />
                </div>
                <div>
                    <label class="label text-xs">Descuento</label>
                    <input type="number" step="0.01" wire:model.live="totalDiscount" class="input w-full text-sm" />
                </div>
            </div>

            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>IVA calculado:</span>
                        <span class="tabular-nums">${{ number_format($totalTax, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold text-slate-900 dark:border-slate-600 dark:text-white">
                        <span>Total:</span>
                        <span class="tabular-nums">${{ number_format($total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Categorización --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Categorización</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Categoría de gasto</label>
                    <select wire:model="expenseCategory" class="input w-full">
                        <option value="">Sin categoría</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-3 pt-6">
                    <input type="checkbox" wire:model="isProcessed" id="isProcessed" class="h-4 w-4 rounded border-slate-300" />
                    <label for="isProcessed" class="text-sm text-slate-700 dark:text-slate-300">
                        Marcar como procesado (contabilizado)
                    </label>
                </div>
            </div>

            <div>
                <label class="label">Notas</label>
                <textarea wire:model="notes" class="input w-full" rows="2" placeholder="Observaciones adicionales..."></textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('panel.received-documents.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $documentId ? 'Actualizar documento' : 'Registrar documento' }}
            </button>
        </div>
    </form>
</div>
