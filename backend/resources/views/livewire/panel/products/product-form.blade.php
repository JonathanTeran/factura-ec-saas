<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('panel.products.index') }}" class="btn-ghost btn-icon shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $product ? 'Editar Producto' : 'Nuevo Producto' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $product ? 'Actualiza los datos del producto o servicio' : 'Registra un nuevo producto o servicio' }}
            </p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Tipo y Código --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Información básica</h3>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="form-group">
                        <label class="form-label">Tipo <span class="text-danger-500">*</span></label>
                        <div class="flex gap-3">
                            <label class="flex-1">
                                <input type="radio" wire:model.live="type" value="product" class="peer sr-only">
                                <div class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:text-primary-700 dark:border-slate-700 dark:text-slate-400 dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-950/30 dark:peer-checked:text-primary-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    Producto
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" wire:model.live="type" value="service" class="peer sr-only">
                                <div class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:text-primary-700 dark:border-slate-700 dark:text-slate-400 dark:peer-checked:border-primary-500 dark:peer-checked:bg-primary-950/30 dark:peer-checked:text-primary-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                                    </svg>
                                    Servicio
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select wire:model="category_id" id="category_id" class="form-input">
                            <option value="">Sin categoría</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="main_code" class="form-label">
                            Código principal <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model="main_code" type="text" id="main_code"
                               placeholder="PROD-0001"
                               class="form-input tabular-nums">
                        @error('main_code') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="aux_code" class="form-label">Código auxiliar</label>
                        <input wire:model="aux_code" type="text" id="aux_code"
                               placeholder="Opcional"
                               class="form-input tabular-nums">
                    </div>
                </div>
            </div>
        </div>

        {{-- Nombre y descripción --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Descripción</h3>

                <div class="grid gap-5">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            Nombre <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model="name" type="text" id="name"
                               placeholder="Nombre del producto o servicio"
                               class="form-input">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea wire:model="description" id="description" rows="3"
                                  placeholder="Descripción detallada del producto o servicio"
                                  class="form-input"></textarea>
                    </div>

                    <div class="form-group sm:max-w-xs">
                        <label for="barcode" class="form-label">Código de barras</label>
                        <input wire:model="barcode" type="text" id="barcode"
                               placeholder="1234567890123"
                               class="form-input tabular-nums">
                    </div>
                </div>
            </div>
        </div>

        {{-- Precios e impuestos --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Precios e impuestos</h3>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="form-group">
                        <label for="unit_price" class="form-label">
                            Precio unitario <span class="text-danger-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">$</span>
                            <input wire:model="unit_price" type="number" step="0.01" min="0" id="unit_price"
                                   placeholder="0.00"
                                   class="form-input !pl-8 tabular-nums">
                        </div>
                        @error('unit_price') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="cost_price" class="form-label">Costo</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">$</span>
                            <input wire:model="cost_price" type="number" step="0.01" min="0" id="cost_price"
                                   placeholder="0.00"
                                   class="form-input !pl-8 tabular-nums">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tax_percentage_code" class="form-label">
                            Tarifa IVA <span class="text-danger-500">*</span>
                        </label>
                        <select wire:model="tax_percentage_code" id="tax_percentage_code" class="form-input">
                            @foreach($taxRates as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unit_of_measure" class="form-label">
                            Unidad de medida <span class="text-danger-500">*</span>
                        </label>
                        <select wire:model="unit_of_measure" id="unit_of_measure" class="form-input">
                            <option value="unidad">Unidad</option>
                            <option value="servicio">Servicio</option>
                            <option value="kg">Kilogramo</option>
                            <option value="g">Gramo</option>
                            <option value="lb">Libra</option>
                            <option value="l">Litro</option>
                            <option value="ml">Mililitro</option>
                            <option value="m">Metro</option>
                            <option value="cm">Centímetro</option>
                            <option value="m2">Metro cuadrado</option>
                            <option value="m3">Metro cúbico</option>
                            <option value="hora">Hora</option>
                            <option value="dia">Día</option>
                            <option value="mes">Mes</option>
                        </select>
                    </div>
                </div>

                {{-- ICE --}}
                <div class="mt-5 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:ring-slate-700/50">
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="$toggle('has_ice')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $has_ice ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                role="switch" aria-checked="{{ $has_ice ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $has_ice ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Aplica ICE (Impuesto a Consumos Especiales)</span>
                    </div>

                    @if($has_ice)
                        <div class="mt-4 sm:max-w-xs">
                            <label for="ice_code" class="form-label">
                                Código ICE <span class="text-danger-500">*</span>
                            </label>
                            <input wire:model="ice_code" type="text" id="ice_code"
                                   placeholder="Código de tarifa ICE"
                                   class="form-input">
                            @error('ice_code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Inventario --}}
        @if($type === 'product')
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Inventario</h3>
                        <button type="button" wire:click="$toggle('track_inventory')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $track_inventory ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                role="switch" aria-checked="{{ $track_inventory ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $track_inventory ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    @if($track_inventory)
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div class="form-group">
                                <label for="current_stock" class="form-label">Stock actual</label>
                                <input wire:model="current_stock" type="number" step="0.01" min="0" id="current_stock"
                                       placeholder="0"
                                       class="form-input tabular-nums">
                            </div>

                            <div class="form-group">
                                <label for="min_stock" class="form-label">Stock mínimo</label>
                                <input wire:model="min_stock" type="number" step="0.01" min="0" id="min_stock"
                                       placeholder="0"
                                       class="form-input tabular-nums">
                                <p class="form-helper">Recibirás alertas cuando el stock esté por debajo de este valor</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Activa el control de inventario para rastrear el stock de este producto.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Opciones --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Opciones</h3>

                <div class="flex flex-wrap gap-6">
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="$toggle('is_active')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $is_active ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                role="switch" aria-checked="{{ $is_active ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Producto activo</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="$toggle('is_favorite')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 {{ $is_favorite ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700' }}"
                                role="switch" aria-checked="{{ $is_favorite ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_favorite ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Marcar como favorito</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('panel.products.index') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">{{ $product ? 'Actualizar' : 'Crear producto' }}</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>
    </form>
</div>
