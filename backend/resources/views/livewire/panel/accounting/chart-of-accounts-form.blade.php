<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $accountId ? 'Editar Cuenta' : 'Nueva Cuenta' }}
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                {{ $accountId ? 'Modificar datos de la cuenta contable' : 'Agregar una nueva cuenta al plan de cuentas' }}
            </p>
        </div>
        <a href="{{ route('panel.accounting.chart-of-accounts') }}" class="btn-secondary">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver al Plan de Cuentas
        </a>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="card p-6">
        <div class="grid gap-6 sm:grid-cols-2">
            {{-- Company --}}
            @if($companies->count() > 1)
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Empresa</label>
                    <select wire:model.live="companyId" class="input w-full" @if($accountId) disabled @endif>
                        <option value="0">Seleccionar empresa...</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->business_name }}</option>
                        @endforeach
                    </select>
                    @error('companyId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Parent Account --}}
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cuenta Padre</label>
                <select wire:model.live="parentId" class="input w-full">
                    <option value="">Sin cuenta padre (cuenta raiz)</option>
                    @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                    @endforeach
                </select>
                @error('parentId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Code --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Codigo</label>
                <input wire:model="code" type="text" class="input w-full font-mono" placeholder="1.01.01.01" />
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nombre</label>
                <input wire:model="name" type="text" class="input w-full" placeholder="Nombre de la cuenta" />
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Account Type --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tipo de Cuenta</label>
                <select wire:model.live="accountType" class="input w-full">
                    @foreach($accountTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('accountType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Account Nature --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Naturaleza</label>
                <select wire:model="accountNature" class="input w-full">
                    <option value="debit">Deudora</option>
                    <option value="credit">Acreedora</option>
                </select>
                @error('accountNature') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Level --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nivel</label>
                <input wire:model="level" type="number" class="input w-full" min="1" max="10" />
                @error('level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Tax Form Code --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Codigo Formulario SRI</label>
                <input wire:model="taxFormCode" type="text" class="input w-full" placeholder="Ej: 311, 411" />
                <p class="mt-1 text-xs text-slate-400">Codigo del casillero en formularios del SRI (opcional)</p>
                @error('taxFormCode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Descripcion</label>
                <textarea wire:model="description" rows="3" class="input w-full" placeholder="Descripcion o notas sobre la cuenta (opcional)"></textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Toggles --}}
            <div class="sm:col-span-2 flex flex-wrap gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input wire:model="allowsMovement" type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700" />
                    <span class="text-sm text-slate-700 dark:text-slate-300">Permite movimiento (cuenta de detalle)</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input wire:model="isActive" type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700" />
                    <span class="text-sm text-slate-700 dark:text-slate-300">Cuenta activa</span>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-700">
            <a href="{{ route('panel.accounting.chart-of-accounts') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $accountId ? 'Actualizar Cuenta' : 'Crear Cuenta' }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Guardando...
                </span>
            </button>
        </div>
    </form>
</div>
