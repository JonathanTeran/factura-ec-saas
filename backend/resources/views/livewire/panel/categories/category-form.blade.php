<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('panel.categories.index') }}" class="btn-ghost btn-icon shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $category ? 'Editar Categoría' : 'Nueva Categoría' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $category ? 'Actualiza los datos de la categoría' : 'Crea una nueva categoría para organizar tus productos' }}
            </p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Información básica --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Información básica</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group sm:col-span-2">
                        <label for="parent_id" class="form-label">Categoría padre</label>
                        <select wire:model="parent_id" id="parent_id" class="form-input">
                            <option value="">Sin categoría padre (raíz)</option>
                            @foreach($parentCategories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label">
                            Nombre <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model.live="name" type="text" id="name"
                               placeholder="Nombre de la categoría"
                               class="form-input">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="slug" class="form-label">
                            Slug <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model="slug" type="text" id="slug"
                               placeholder="nombre-de-la-categoria"
                               {{ $category ? 'readonly' : '' }}
                               class="form-input {{ $category ? 'bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                        @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group sm:col-span-2">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea wire:model="description" id="description" rows="3"
                                  placeholder="Descripción de la categoría (opcional)"
                                  class="form-input"></textarea>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Apariencia --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Apariencia</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="color" class="form-label">
                            Color <span class="text-danger-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input wire:model="color" type="color" id="color"
                                   class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-800">
                            <input wire:model="color" type="text" placeholder="#3b82f6"
                                   class="form-input flex-1 font-mono tabular-nums">
                        </div>
                        @error('color') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="icon" class="form-label">Icono</label>
                        <input wire:model="icon" type="text" id="icon"
                               placeholder="Ej: shopping-bag, box, tag"
                               class="form-input">
                        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Nombre del icono o emoji (opcional)</p>
                        @error('icon') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuración --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Configuración</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="sort_order" class="form-label">
                            Orden <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model="sort_order" type="number" min="0" id="sort_order"
                               placeholder="0"
                               class="form-input tabular-nums">
                        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Las categorías se ordenan de menor a mayor</p>
                        @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group flex items-end">
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="$toggle('is_active')"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $is_active ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                    role="switch" aria-checked="{{ $is_active ? 'true' : 'false' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Categoría activa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('panel.categories.index') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">{{ $category ? 'Actualizar' : 'Crear categoría' }}</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>
    </form>
</div>
