<div>
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol role="list" class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('tenant.products.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Productos
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="size-5 shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $isEditing ? 'Editar' : 'Nuevo' }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
            {{ $isEditing ? 'Editar Producto' : 'Nuevo Producto' }}
        </h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Basic Info -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Información Básica</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <!-- Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Código <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="code" type="text" id="code"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                            placeholder="PROD001">
                        @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            SKU
                        </label>
                        <input wire:model="sku" type="text" id="sku"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                            placeholder="SKU-001">
                    </div>

                    <!-- Type -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="type" id="type"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                            <option value="product">Bien</option>
                            <option value="service">Servicio</option>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="sm:col-span-3">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name" type="text" id="name"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                            placeholder="Nombre del producto o servicio">
                        @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-3">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Descripción
                        </label>
                        <textarea wire:model="description" id="description" rows="2"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                            placeholder="Descripción del producto"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Precios e Impuestos</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <!-- Unit Price -->
                    <div>
                        <label for="unit_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Precio Unitario <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input wire:model="unit_price" type="number" step="0.01" id="unit_price"
                                class="block w-full rounded-md border-0 py-2 pl-7 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                                placeholder="0.00">
                        </div>
                        @error('unit_price')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cost -->
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Costo
                        </label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input wire:model="cost" type="number" step="0.01" id="cost"
                                class="block w-full rounded-md border-0 py-2 pl-7 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                                placeholder="0.00">
                        </div>
                    </div>

                    <!-- Tax Rate -->
                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tarifa IVA
                        </label>
                        <select wire:model="tax_rate" id="tax_rate"
                            class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm">
                            <option value="0">0% - No objeto de impuesto</option>
                            <option value="12">12% - IVA</option>
                            <option value="15">15% - IVA</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Inventario</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="space-y-6">
                    <!-- Track Inventory -->
                    <div class="flex items-center">
                        <input wire:model.live="track_inventory" type="checkbox" id="track_inventory"
                            class="size-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700">
                        <label for="track_inventory" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            Controlar inventario
                        </label>
                    </div>

                    @if($track_inventory)
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Stock -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Stock Actual
                            </label>
                            <input wire:model="stock" type="number" id="stock"
                                class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                                placeholder="0">
                        </div>

                        <!-- Min Stock -->
                        <div>
                            <label for="min_stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Stock Mínimo
                            </label>
                            <input wire:model="min_stock" type="number" id="min_stock"
                                class="mt-1 block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:ring-gray-600 sm:text-sm"
                                placeholder="0">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Alerta cuando el stock llegue a este nivel</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <input wire:model="is_active" type="checkbox" id="is_active"
                        class="size-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                        Producto activo
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('tenant.products.index') }}"
                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                Cancelar
            </a>
            <button type="submit"
                class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
            </button>
        </div>
    </form>
</div>
