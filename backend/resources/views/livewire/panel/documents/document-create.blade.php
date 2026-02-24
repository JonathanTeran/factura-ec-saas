<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('panel.documents.index') }}"
           class="btn-ghost btn-icon shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $this->documentTitle }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $this->documentSubtitle }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Form --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Datos generales --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Datos generales</h3>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="branch_id" class="form-label">
                                Establecimiento <span class="text-danger-500">*</span>
                            </label>
                            <select wire:model.live="branch_id" id="branch_id" class="form-input">
                                <option value="">Seleccionar...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->code }} - {{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label for="emission_point_id" class="form-label">
                                Punto de emision <span class="text-danger-500">*</span>
                            </label>
                            <select wire:model="emission_point_id" id="emission_point_id"
                                    class="form-input disabled:opacity-50"
                                    {{ !$branch_id ? 'disabled' : '' }}>
                                <option value="">Seleccionar...</option>
                                @foreach($emissionPoints as $point)
                                    <option value="{{ $point->id }}">{{ $point->code }} - {{ $point->name ?? 'Punto de emision' }}</option>
                                @endforeach
                            </select>
                            @error('emission_point_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label for="issue_date" class="form-label">
                                Fecha de emision <span class="text-danger-500">*</span>
                            </label>
                            <input wire:model="issue_date" type="date" id="issue_date" class="form-input">
                            @error('issue_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label for="due_date" class="form-label">Fecha de vencimiento</label>
                            <input wire:model="due_date" type="date" id="due_date" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Documento de referencia (NC/ND) --}}
            @if($this->needsRelatedDocument)
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Documento de referencia</h3>

                        @if($selectedRelatedDocument)
                            <div class="flex items-center justify-between rounded-xl bg-primary-50 p-4 ring-1 ring-primary-100 dark:bg-primary-950/30 dark:ring-primary-900/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-sm font-semibold text-white">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Factura {{ $selectedRelatedDocument->series }}-{{ str_pad($selectedRelatedDocument->sequential, 9, '0', STR_PAD_LEFT) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $selectedRelatedDocument->issue_date->format('d/m/Y') }}
                                            &middot; {{ $selectedRelatedDocument->customer->business_name }}
                                            &middot; ${{ number_format($selectedRelatedDocument->total, 2) }}
                                        </p>
                                    </div>
                                </div>
                                <button wire:click="clearRelatedDocument" type="button"
                                        class="rounded-lg p-2 text-slate-400 hover:bg-white/60 hover:text-slate-600 dark:hover:bg-slate-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                </div>
                                <input wire:model.live.debounce.300ms="relatedDocumentSearch" type="text"
                                       placeholder="Buscar factura por numero, nombre o identificacion del cliente..."
                                       class="form-input !py-3 !pl-11">

                                @if($relatedDocuments->count() > 0)
                                    <div class="dropdown-menu absolute z-10 mt-2 w-full p-1.5">
                                        <ul class="max-h-60 overflow-auto">
                                            @foreach($relatedDocuments as $relDoc)
                                                <li>
                                                    <button wire:click="selectRelatedDocument({{ $relDoc->id }})" type="button"
                                                            class="dropdown-item w-full justify-between">
                                                        <div class="text-left">
                                                            <p class="font-medium text-slate-900 dark:text-white">
                                                                {{ $relDoc->series }}-{{ str_pad($relDoc->sequential, 9, '0', STR_PAD_LEFT) }}
                                                            </p>
                                                            <p class="text-xs text-slate-500 tabular-nums">
                                                                {{ $relDoc->issue_date->format('d/m/Y') }} &middot; {{ $relDoc->customer->business_name }}
                                                            </p>
                                                        </div>
                                                        <span class="font-semibold tabular-nums text-slate-900 dark:text-white">${{ number_format($relDoc->total, 2) }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            @error('related_document_id') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        @endif

                        <div class="mt-5">
                            <label for="modification_reason" class="form-label">
                                Motivo de modificacion <span class="text-danger-500">*</span>
                            </label>
                            <textarea wire:model="modification_reason" id="modification_reason" rows="3"
                                      placeholder="Describa el motivo de la modificacion..."
                                      class="form-input"></textarea>
                            @error('modification_reason') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- Cliente --}}
            <div class="card">
                <div class="card-body">
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Cliente</h3>
                        <a href="{{ route('panel.customers.create') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            + Nuevo cliente
                        </a>
                    </div>

                    @if($selectedCustomer)
                        <div class="flex items-center justify-between rounded-xl bg-primary-50 p-4 ring-1 ring-primary-100 dark:bg-primary-950/30 dark:ring-primary-900/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-sm font-semibold text-white">
                                    {{ strtoupper(substr($selectedCustomer->business_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $selectedCustomer->business_name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">{{ $selectedCustomer->identification }}</p>
                                </div>
                            </div>
                            <button wire:click="clearCustomer" type="button"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-white/60 hover:text-slate-600 dark:hover:bg-slate-800">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="customerSearch" type="text"
                                   placeholder="Buscar por nombre, cedula o RUC..."
                                   class="form-input !py-3 !pl-11">

                            @if($customers->count() > 0)
                                <div class="dropdown-menu absolute z-10 mt-2 w-full p-1.5">
                                    <ul class="max-h-60 overflow-auto">
                                        @foreach($customers as $customer)
                                            <li>
                                                <button wire:click="selectCustomer({{ $customer->id }})" type="button"
                                                        class="dropdown-item w-full gap-3">
                                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                        {{ strtoupper(substr($customer->business_name, 0, 1)) }}
                                                    </div>
                                                    <div class="text-left">
                                                        <p class="font-medium text-slate-900 dark:text-white">{{ $customer->business_name }}</p>
                                                        <p class="text-xs text-slate-500 tabular-nums">{{ $customer->identification }}</p>
                                                    </div>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        @error('customer_id') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    @endif
                </div>
            </div>

            {{-- Productos (Facturas, NC, ND) --}}
            @if($this->needsItems)
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Productos</h3>

                        <div class="relative mb-5">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="productSearch" type="text"
                                   placeholder="Buscar producto por nombre, codigo o codigo de barras..."
                                   class="form-input !py-3 !pl-11">

                            @if($products->count() > 0)
                                <div class="dropdown-menu absolute z-10 mt-2 w-full p-1.5">
                                    <ul class="max-h-60 overflow-auto">
                                        @foreach($products as $product)
                                            <li>
                                                <button wire:click="addProduct({{ $product->id }})" type="button"
                                                        class="dropdown-item w-full justify-between">
                                                    <div class="text-left">
                                                        <p class="font-medium text-slate-900 dark:text-white">{{ $product->name }}</p>
                                                        <p class="text-xs text-slate-500 tabular-nums">{{ $product->main_code }}</p>
                                                    </div>
                                                    <span class="font-semibold tabular-nums text-slate-900 dark:text-white">${{ number_format($product->unit_price, 2) }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        @if(count($items) > 0)
                            <div class="overflow-x-auto -mx-6">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700">
                                            <th class="pb-3 pl-6 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Producto</th>
                                            <th class="pb-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 w-24">Cant.</th>
                                            <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 w-28">Precio</th>
                                            <th class="pb-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 w-20">Desc. %</th>
                                            <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 w-28">Subtotal</th>
                                            <th class="pb-3 pr-6 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                        @foreach($items as $index => $item)
                                            <tr>
                                                <td class="py-4 pl-6">
                                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $item['description'] }}</p>
                                                    <p class="text-xs text-slate-400 dark:text-slate-500 tabular-nums">{{ $item['main_code'] }}</p>
                                                </td>
                                                <td class="py-4">
                                                    <input wire:change="updateItemQuantity({{ $index }}, $event.target.value)"
                                                           type="number" step="0.01" min="0.0001"
                                                           value="{{ $item['quantity'] }}"
                                                           class="form-input !py-1.5 text-center tabular-nums">
                                                </td>
                                                <td class="py-4">
                                                    <input wire:change="updateItemPrice({{ $index }}, $event.target.value)"
                                                           type="number" step="0.01" min="0"
                                                           value="{{ $item['unit_price'] }}"
                                                           class="form-input !py-1.5 text-right tabular-nums">
                                                </td>
                                                <td class="py-4">
                                                    <input wire:change="updateItemDiscount({{ $index }}, $event.target.value)"
                                                           type="number" step="0.01" min="0" max="100"
                                                           value="{{ $item['discount_percent'] }}"
                                                           class="form-input !py-1.5 text-center tabular-nums">
                                                </td>
                                                <td class="py-4 text-right font-medium tabular-nums text-slate-900 dark:text-white text-sm">
                                                    ${{ number_format($item['subtotal'], 2) }}
                                                </td>
                                                <td class="py-4 pr-6">
                                                    <button wire:click="removeItem({{ $index }})" type="button"
                                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-700">
                                <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">No hay productos agregados</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">Busca un producto arriba para agregarlo</p>
                            </div>
                        @endif
                        @error('items') <p class="form-error mt-3">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            {{-- Detalles de retencion (Retenciones) --}}
            @if($this->isRetention)
                <div class="card">
                    <div class="card-body">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Detalles de retencion</h3>
                            <button wire:click="addWithholdingDetail" type="button"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                + Agregar detalle
                            </button>
                        </div>

                        @if(count($withholding_details) > 0)
                            <div class="space-y-4">
                                @foreach($withholding_details as $index => $detail)
                                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:ring-slate-700/50">
                                        <div class="mb-3 flex items-center justify-between">
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Detalle {{ $index + 1 }}</span>
                                            <button wire:click="removeWithholdingDetail({{ $index }})" type="button"
                                                    class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="form-group">
                                                <label class="form-label">Tipo de impuesto <span class="text-danger-500">*</span></label>
                                                <select wire:model.live="withholding_details.{{ $index }}.tax_type" class="form-input">
                                                    <option value="renta">Renta</option>
                                                    <option value="iva">IVA</option>
                                                    <option value="isd">ISD</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Codigo del impuesto</label>
                                                <input wire:model="withholding_details.{{ $index }}.tax_code"
                                                       type="text"
                                                       placeholder="Ej: 303"
                                                       class="form-input">
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Codigo de retencion <span class="text-danger-500">*</span></label>
                                                <input wire:model="withholding_details.{{ $index }}.withholding_code"
                                                       type="text"
                                                       placeholder="Ej: 303"
                                                       class="form-input">
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Porcentaje de retencion <span class="text-danger-500">*</span></label>
                                                <div class="relative">
                                                    <input wire:model.live="withholding_details.{{ $index }}.withholding_percentage"
                                                           type="number" step="0.01" min="0" max="100"
                                                           placeholder="0.00"
                                                           class="form-input !pr-8 tabular-nums">
                                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500">%</span>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Base imponible <span class="text-danger-500">*</span></label>
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">$</span>
                                                    <input wire:model.live="withholding_details.{{ $index }}.base_amount"
                                                           type="number" step="0.01" min="0"
                                                           placeholder="0.00"
                                                           class="form-input !pl-7 tabular-nums">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Valor retenido</label>
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">$</span>
                                                    <input type="text"
                                                           value="{{ number_format($detail['withheld_amount'] ?? 0, 2) }}"
                                                           readonly
                                                           class="form-input !pl-7 tabular-nums bg-slate-50 dark:bg-slate-800 cursor-not-allowed">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Total retenido --}}
                            <div class="mt-5 flex justify-end">
                                <div class="rounded-xl bg-primary-50 px-6 py-3 ring-1 ring-primary-100 dark:bg-primary-950/30 dark:ring-primary-900/50">
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total retenido</span>
                                        <span class="text-lg font-bold tabular-nums text-primary-600 dark:text-primary-400">${{ number_format($total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-700">
                                <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">No hay detalles de retencion</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">Haz clic en "+ Agregar detalle" para comenzar</p>
                            </div>
                        @endif
                        @error('withholding_details') <p class="form-error mt-3">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            {{-- Datos de transporte (Guia de Remision) --}}
            @if($this->isGuide)
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Datos de transporte</h3>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="form-group">
                                <label for="carrier_ruc" class="form-label">
                                    RUC del transportista <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="carrier_ruc" type="text" id="carrier_ruc"
                                       placeholder="Ej: 0990000000001"
                                       maxlength="13"
                                       class="form-input tabular-nums">
                                @error('carrier_ruc') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label for="carrier_name" class="form-label">
                                    Nombre del transportista <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="carrier_name" type="text" id="carrier_name"
                                       placeholder="Razon social del transportista"
                                       class="form-input">
                                @error('carrier_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label for="carrier_plate" class="form-label">
                                    Placa del vehiculo <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="carrier_plate" type="text" id="carrier_plate"
                                       placeholder="Ej: ABC-1234"
                                       class="form-input uppercase">
                                @error('carrier_plate') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label for="transport_start_date" class="form-label">
                                    Fecha de inicio de transporte <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="transport_start_date" type="date" id="transport_start_date"
                                       class="form-input">
                                @error('transport_start_date') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Datos de destino</h3>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="form-group sm:col-span-2">
                                <label for="origin_address" class="form-label">
                                    Direccion de partida <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="origin_address" type="text" id="origin_address"
                                       placeholder="Direccion de origen de la mercaderia"
                                       class="form-input">
                                @error('origin_address') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group sm:col-span-2">
                                <label for="destination_address" class="form-label">
                                    Direccion de destino <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="destination_address" type="text" id="destination_address"
                                       placeholder="Direccion de destino de la mercaderia"
                                       class="form-input">
                                @error('destination_address') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label for="destination_ruc" class="form-label">
                                    RUC / CI del destinatario <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="destination_ruc" type="text" id="destination_ruc"
                                       placeholder="Identificacion del destinatario"
                                       maxlength="13"
                                       class="form-input tabular-nums">
                                @error('destination_ruc') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label for="destination_name" class="form-label">
                                    Nombre del destinatario <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="destination_name" type="text" id="destination_name"
                                       placeholder="Razon social del destinatario"
                                       class="form-input">
                                @error('destination_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Notas --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Notas adicionales</h3>
                    <textarea wire:model="notes" rows="3"
                              placeholder="Observaciones o notas adicionales para el documento..."
                              class="form-input"></textarea>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Resumen de totales --}}
            <div class="card sticky top-6">
                <div class="card-body">
                    <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Resumen</h3>

                    <dl class="space-y-3">
                        @if($this->needsItems)
                            @if($subtotal_0 > 0)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">Subtotal 0%</dt>
                                    <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($subtotal_0, 2) }}</dd>
                                </div>
                            @endif
                            @if($subtotal_5 > 0)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">Subtotal 5%</dt>
                                    <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($subtotal_5, 2) }}</dd>
                                </div>
                            @endif
                            @if($subtotal_12 > 0)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">Subtotal 12%</dt>
                                    <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($subtotal_12, 2) }}</dd>
                                </div>
                            @endif
                            @if($subtotal_15 > 0)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">Subtotal 15%</dt>
                                    <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($subtotal_15, 2) }}</dd>
                                </div>
                            @endif

                            <div class="flex justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                                <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($subtotal, 2) }}</dd>
                            </div>

                            @if($total_discount > 0)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">Descuento</dt>
                                    <dd class="font-medium tabular-nums text-danger-600 dark:text-red-400">-${{ number_format($total_discount, 2) }}</dd>
                                </div>
                            @endif

                            <div class="flex justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">IVA</dt>
                                <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($total_tax, 2) }}</dd>
                            </div>

                            @if($this->isInvoice)
                                <div class="flex items-center justify-between">
                                    <label for="tip" class="text-sm text-slate-500 dark:text-slate-400">Propina</label>
                                    <div class="relative w-28">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">$</span>
                                        <input wire:model.live="tip" type="number" step="0.01" min="0" id="tip"
                                               class="form-input !py-1.5 !pl-7 text-right tabular-nums">
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($this->isRetention)
                            @foreach($withholding_details as $index => $detail)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-slate-500 dark:text-slate-400">
                                        {{ ucfirst($detail['tax_type']) }}
                                        @if($detail['withholding_code'])
                                            ({{ $detail['withholding_code'] }})
                                        @endif
                                        {{ $detail['withholding_percentage'] }}%
                                    </dt>
                                    <dd class="font-medium tabular-nums text-slate-900 dark:text-white">${{ number_format($detail['withheld_amount'] ?? 0, 2) }}</dd>
                                </div>
                            @endforeach
                        @endif

                        <div class="border-t border-slate-200 pt-3 dark:border-slate-700">
                            <div class="flex justify-between">
                                <dt class="text-lg font-semibold text-slate-900 dark:text-white">Total</dt>
                                <dd class="text-lg font-bold tabular-nums text-primary-600 dark:text-primary-400">${{ number_format($total, 2) }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Formas de pago (solo facturas) --}}
            @if($this->needsPaymentMethods)
                <div class="card">
                    <div class="card-body">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Forma de pago</h3>
                            <button wire:click="addPaymentMethod" type="button"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                + Agregar
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach($payment_methods as $index => $method)
                                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:ring-slate-700/50">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Pago {{ $index + 1 }}</span>
                                        @if(count($payment_methods) > 1)
                                            <button wire:click="removePaymentMethod({{ $index }})" type="button"
                                                    class="text-slate-400 hover:text-rose-600 transition-colors">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>

                                    <div class="space-y-3">
                                        <select wire:model="payment_methods.{{ $index }}.code" class="form-input !py-2">
                                            <option value="01">Sin utilizacion del sistema financiero</option>
                                            <option value="15">Compensacion de deudas</option>
                                            <option value="16">Tarjeta de debito</option>
                                            <option value="17">Dinero electronico</option>
                                            <option value="18">Tarjeta prepago</option>
                                            <option value="19">Tarjeta de credito</option>
                                            <option value="20">Otros con utilizacion del sistema financiero</option>
                                            <option value="21">Endoso de titulos</option>
                                        </select>

                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">$</span>
                                            <input wire:model="payment_methods.{{ $index }}.amount"
                                                   type="number" step="0.01" min="0"
                                                   placeholder="Monto"
                                                   class="form-input !py-2 !pl-7 tabular-nums">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Acciones --}}
            <div class="space-y-3">
                <button wire:click="saveAndProcess" type="button" class="btn-primary w-full justify-center !py-3">
                    <svg wire:loading wire:target="saveAndProcess" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="saveAndProcess">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="saveAndProcess">{{ $this->emitButtonText }}</span>
                    <span wire:loading wire:target="saveAndProcess">Procesando...</span>
                </button>

                <button wire:click="saveDraft" type="button" class="btn-outline w-full justify-center !py-3">
                    <svg wire:loading wire:target="saveDraft" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="saveDraft">Guardar borrador</span>
                    <span wire:loading wire:target="saveDraft">Guardando...</span>
                </button>

                <a href="{{ route('panel.documents.index') }}"
                   class="block text-center text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>
