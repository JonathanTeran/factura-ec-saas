<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('panel.customers.index') }}" class="btn-ghost btn-icon shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $customer ? 'Editar Cliente' : 'Nuevo Cliente' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $customer ? 'Actualiza los datos del cliente' : 'Registra un nuevo cliente para facturar' }}
            </p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Identificación --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Identificación</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="identification_type" class="form-label">
                            Tipo de identificación <span class="text-danger-500">*</span>
                        </label>
                        <select wire:model.live="identification_type" id="identification_type" class="form-input">
                            <option value="ruc">RUC</option>
                            <option value="cedula">Cédula</option>
                            <option value="pasaporte">Pasaporte</option>
                            <option value="consumidor_final">Consumidor Final</option>
                            <option value="exterior">Identificación del Exterior</option>
                        </select>
                        @error('identification_type') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="identification" class="form-label">
                            Número de identificación <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model.blur="identification" wire:blur="validateIdentification" type="text" id="identification"
                               placeholder="{{ $identification_type === 'ruc' ? '1234567890001' : '1234567890' }}"
                               {{ $identification_type === 'consumidor_final' ? 'disabled' : '' }}
                               class="form-input disabled:bg-slate-50 disabled:text-slate-500 dark:disabled:bg-slate-800 tabular-nums">
                        @error('identification') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos del cliente --}}
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5 text-base font-semibold text-slate-900 dark:text-white">Datos del cliente</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group sm:col-span-2">
                        <label for="business_name" class="form-label">
                            Razón social / Nombre <span class="text-danger-500">*</span>
                        </label>
                        <input wire:model="business_name" type="text" id="business_name"
                               placeholder="Nombre completo o razón social"
                               {{ $identification_type === 'consumidor_final' ? 'disabled' : '' }}
                               class="form-input disabled:bg-slate-50 disabled:text-slate-500 dark:disabled:bg-slate-800">
                        @error('business_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group sm:col-span-2">
                        <label for="trade_name" class="form-label">Nombre comercial</label>
                        <input wire:model="trade_name" type="text" id="trade_name"
                               placeholder="Nombre comercial (opcional)"
                               class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input wire:model="email" type="email" id="email"
                               placeholder="cliente@ejemplo.com"
                               class="form-input">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input wire:model="phone" type="tel" id="phone"
                               placeholder="0999999999"
                               class="form-input tabular-nums">
                    </div>

                    <div class="form-group sm:col-span-2">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea wire:model="address" id="address" rows="2"
                                  placeholder="Dirección completa del cliente"
                                  class="form-input"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="$toggle('is_active')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $is_active ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                role="switch" aria-checked="{{ $is_active ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Cliente activo</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('panel.customers.index') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">{{ $customer ? 'Actualizar' : 'Crear cliente' }}</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        </div>
    </form>
</div>
