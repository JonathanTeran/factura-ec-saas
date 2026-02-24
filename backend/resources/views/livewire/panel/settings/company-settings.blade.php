<div class="space-y-8">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('panel.settings.index') }}"
           class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Configuración de Empresa
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Gestiona los datos de tu empresa, certificado digital y sucursales
            </p>
        </div>
    </div>

    {{-- Estado de configuración --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Estado del emisor</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Antes de emitir, completa este checklist obligatorio.
                </p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $emitterReady ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                <span class="h-2 w-2 rounded-full {{ $emitterReady ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $emitterReady ? 'Listo para facturar' : 'Pendiente de configuración' }}
            </span>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach($readinessItems as $item)
                <div class="rounded-xl border px-4 py-3 {{ $item['ready'] ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-900/15' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40' }}">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full {{ $item['ready'] ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                            @if($item['ready'])
                                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $item['label'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Datos de la empresa --}}
    <form wire:submit="updateCompany" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Datos del emisor</h3>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {{-- RUC --}}
            <div>
                <label for="ruc" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    RUC <span class="text-red-500">*</span>
                </label>
                <input wire:model="ruc" type="text" id="ruc" maxlength="13"
                       placeholder="1234567890001"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                @error('ruc')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo de persona --}}
            <div>
                <label for="taxpayer_type" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Tipo de persona <span class="text-red-500">*</span>
                </label>
                <select wire:model="taxpayer_type" id="taxpayer_type"
                        class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                    <option value="natural">Persona natural</option>
                    <option value="juridical">Persona jurídica</option>
                    <option value="rise">RISE</option>
                </select>
                @error('taxpayer_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Régimen tributario --}}
            <div>
                <label for="tax_regime" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Régimen tributario <span class="text-red-500">*</span>
                </label>
                <select wire:model="tax_regime" id="tax_regime"
                        class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                    <option value="general">Contribuyente régimen general</option>
                    <option value="rimpe_emprendedor">RIMPE emprendedor</option>
                    <option value="rimpe_popular">RIMPE negocio popular</option>
                    <option value="sociedad_simplificada">Régimen sociedades simplificadas</option>
                </select>
                @error('tax_regime')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Razón social --}}
            <div class="lg:col-span-2">
                <label for="business_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Razón social <span class="text-red-500">*</span>
                </label>
                <input wire:model="business_name" type="text" id="business_name"
                       placeholder="Nombre de la empresa según RUC"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                @error('business_name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nombre comercial --}}
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="trade_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Nombre comercial
                </label>
                <input wire:model="trade_name" type="text" id="trade_name"
                       placeholder="Nombre comercial de la empresa"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
            </div>

            {{-- Ambiente --}}
            <div>
                <label for="environment" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Ambiente SRI <span class="text-red-500">*</span>
                </label>
                <select wire:model="environment" id="environment"
                        class="block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                    <option value="1">Pruebas</option>
                    <option value="2">Producción</option>
                </select>
            </div>

            {{-- Clave SRI --}}
            <div>
                <label for="sri_password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Clave SRI <span class="text-red-500">*</span>
                </label>
                <input wire:model="sri_password" type="password" id="sri_password"
                       autocomplete="new-password"
                       placeholder="{{ $company?->hasSriPassword() ? '******** (dejar vacío para conservarla)' : 'Ingresa la clave del SRI' }}"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                @if($company?->hasSriPassword())
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ya existe una clave guardada. Completa este campo solo para reemplazarla.</p>
                @endif
                @error('sri_password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Dirección --}}
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="address" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Dirección matriz <span class="text-red-500">*</span>
                </label>
                <input wire:model="address" type="text" id="address"
                       placeholder="Dirección de la matriz"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                @error('address')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="company_email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Email <span class="text-red-500">*</span>
                </label>
                <input wire:model="email" type="email" id="company_email"
                       placeholder="empresa@ejemplo.com"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Teléfono --}}
            <div>
                <label for="company_phone" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Teléfono
                </label>
                <input wire:model="phone" type="tel" id="company_phone"
                       placeholder="0999999999"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
            </div>

            {{-- Obligado a llevar contabilidad --}}
            <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-3">
                <button type="button" wire:click="$toggle('accounting_required')"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 {{ $accounting_required ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                        role="switch" aria-checked="{{ $accounting_required ? 'true' : 'false' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $accounting_required ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Obligado a llevar contabilidad</span>
            </div>

            {{-- Contribuyente especial --}}
            <div class="space-y-3 sm:col-span-2 lg:col-span-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="$toggle('special_taxpayer')"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 {{ $special_taxpayer ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                            role="switch" aria-checked="{{ $special_taxpayer ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $special_taxpayer ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Contribuyente especial</span>
                </div>

                @if($special_taxpayer)
                    <div>
                        <label for="special_taxpayer_number" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Resolución N°
                        </label>
                        <input wire:model="special_taxpayer_number" type="text" id="special_taxpayer_number"
                               placeholder="Ej: 12345"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        @error('special_taxpayer_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- Agente de retención --}}
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="retention_agent_number" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Resolución N° de agente de retención
                </label>
                <input wire:model="retention_agent_number" type="text" id="retention_agent_number"
                       placeholder="Opcional"
                       class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                @error('retention_agent_number')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Artesano calificado --}}
            <div class="space-y-3 sm:col-span-2 lg:col-span-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="$toggle('artisan_qualified')"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 {{ $artisan_qualified ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                            role="switch" aria-checked="{{ $artisan_qualified ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $artisan_qualified ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Artesano calificado</span>
                </div>

                @if($artisan_qualified)
                    <div>
                        <label for="artisan_qualification_number" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Calificación N°
                        </label>
                        <input wire:model="artisan_qualification_number" type="text" id="artisan_qualification_number"
                               placeholder="Número de calificación"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        @error('artisan_qualification_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- Transportista --}}
            <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-3">
                <button type="button" wire:click="$toggle('is_transporter')"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 {{ $is_transporter ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                        role="switch" aria-checked="{{ $is_transporter ? 'true' : 'false' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_transporter ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Es transportista</span>
            </div>
        </div>

        {{-- Logo --}}
        <div class="mt-6 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Logo de la empresa</label>
            <div class="flex items-center gap-4">
                @if($company?->logo_path)
                    <img src="{{ Storage::url($company->logo_path) }}" alt="Logo"
                         class="h-16 w-16 rounded-xl object-contain bg-white p-1 ring-1 ring-slate-200 dark:ring-slate-700">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-200 dark:bg-slate-700">
                        <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                @endif
                <label class="cursor-pointer rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Subir logo
                    <input wire:model="logo" type="file" accept="image/*" class="sr-only">
                </label>
                @if($logo)
                    <span class="text-sm text-slate-500">{{ $logo->getClientOriginalName() }}</span>
                @endif
            </div>
            @error('logo')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900">
                <svg wire:loading wire:target="updateCompany" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="updateCompany">Guardar cambios</span>
                <span wire:loading wire:target="updateCompany">Guardando...</span>
            </button>
        </div>
    </form>

    {{-- Certificado digital --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">Certificado digital (firma electrónica)</h3>

        @if($certificateInfo)
            {{-- Certificado cargado --}}
            <div class="mb-6 rounded-xl {{ $certificateInfo['is_expiring_soon'] ? 'bg-amber-50 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:ring-amber-800' : 'bg-emerald-50 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:ring-emerald-800' }} p-4">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $certificateInfo['is_expiring_soon'] ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold {{ $certificateInfo['is_expiring_soon'] ? 'text-amber-900 dark:text-amber-100' : 'text-emerald-900 dark:text-emerald-100' }}">
                            {{ $certificateInfo['issued_to'] ?? 'Certificado válido' }}
                        </h4>
                        <p class="mt-1 text-sm {{ $certificateInfo['is_expiring_soon'] ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                            Emitido por: {{ $certificateInfo['issued_by'] ?? 'N/A' }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-4 text-sm">
                            <span class="{{ $certificateInfo['is_expiring_soon'] ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                Válido desde: {{ $certificateInfo['valid_from']?->format('d/m/Y') ?? 'N/A' }}
                            </span>
                            <span class="{{ $certificateInfo['is_expiring_soon'] ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                Válido hasta: {{ $certificateInfo['valid_until']?->format('d/m/Y') ?? 'N/A' }}
                            </span>
                            @if($certificateInfo['days_remaining'])
                                <span class="font-semibold {{ $certificateInfo['is_expiring_soon'] ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                                    ({{ abs($certificateInfo['days_remaining']) }} días restantes)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Formulario para subir certificado --}}
        <form wire:submit="uploadCertificate" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="certificate" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Archivo del certificado (.p12, .pfx) <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="certificate" type="file" id="certificate" accept=".p12,.pfx"
                           class="block w-full rounded-xl border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:file:bg-blue-900/30 dark:file:text-blue-400">
                    @error('certificate')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="certificate_password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Contraseña del certificado <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="certificate_password" type="password" id="certificate_password"
                           placeholder="Contraseña"
                           class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                    @error('certificate_password')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-slate-700 dark:hover:bg-slate-600 dark:focus:ring-offset-slate-900">
                    <svg wire:loading wire:target="uploadCertificate" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="uploadCertificate">Cargar certificado</span>
                    <span wire:loading wire:target="uploadCertificate">Cargando...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Sucursales y puntos de emisión --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Sucursales</h3>
            <button wire:click="openBranchModal" type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva sucursal
            </button>
        </div>

        @if(count($branches) > 0)
            <div class="space-y-4">
                @foreach($branches as $branch)
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    <span class="text-sm font-bold">{{ $branch['code'] }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ $branch['name'] }}</h4>
                                        @if($branch['is_main'])
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Principal</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $branch['address'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="openBranchModal({{ $branch['id'] }})" type="button"
                                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </button>
                                <button wire:click="deleteBranch({{ $branch['id'] }})"
                                        wire:confirm="¿Estás seguro de eliminar esta sucursal?"
                                        type="button"
                                        class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Puntos de emisión --}}
                        @if(isset($branch['emission_points']) && count($branch['emission_points']) > 0)
                            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                                <p class="mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Puntos de emisión:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($branch['emission_points'] as $point)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-200 px-3 py-1 text-sm font-medium text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                            {{ $point['code'] }} - {{ $point['name'] ?? 'Punto de emisión' }}
                                            <button wire:click="openSequentialModal({{ $point['id'] }})" type="button"
                                                    title="Configurar secuenciales"
                                                    class="ml-1 rounded p-0.5 text-slate-400 transition hover:bg-slate-300 hover:text-slate-600 dark:hover:bg-slate-600 dark:hover:text-slate-200">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-700">
                <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                </svg>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No hay sucursales configuradas</p>
                <button wire:click="openBranchModal" type="button"
                        class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    + Agregar primera sucursal
                </button>
            </div>
        @endif
    </div>

    {{-- Notificaciones WhatsApp --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-800 dark:ring-white/10">
        <h3 class="mb-6 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            Notificaciones WhatsApp
        </h3>

        @if(!$this->planSupportsWhatsApp)
            {{-- Plan no soporta WhatsApp --}}
            <div class="rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200 dark:bg-slate-900/50 dark:ring-slate-700">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-slate-900 dark:text-white">Función no disponible en tu plan actual</h4>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Las notificaciones por WhatsApp requieren un plan superior. Actualiza tu plan para recibir alertas instantáneas sobre el estado de tus documentos electrónicos.
                        </p>
                        <a href="{{ route('panel.settings.billing') }}"
                           class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700">
                            Ver planes disponibles
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- WhatsApp habilitado en el plan --}}
            <div class="space-y-6">
                {{-- Toggle activar/desactivar --}}
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="$toggle('whatsapp_enabled')"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 {{ $whatsapp_enabled ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                            role="switch" aria-checked="{{ $whatsapp_enabled ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $whatsapp_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Activar notificaciones por WhatsApp</span>
                </div>

                {{-- Campo de teléfono --}}
                @if($whatsapp_enabled)
                    <div>
                        <label for="whatsapp_phone" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Número de WhatsApp
                        </label>
                        <input wire:model="whatsapp_phone" type="tel" id="whatsapp_phone"
                               placeholder="+593 999 999 999"
                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                            Número con código de país para recibir notificaciones de documentos
                        </p>
                        @error('whatsapp_phone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Descripción --}}
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Recibirás notificaciones cuando tus documentos sean autorizados o rechazados por el SRI.
                </p>

                {{-- Botón guardar --}}
                <div class="flex justify-end">
                    <button wire:click="updateWhatsAppSettings" type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900">
                        <svg wire:loading wire:target="updateWhatsAppSettings" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="updateWhatsAppSettings">Guardar configuración</span>
                        <span wire:loading wire:target="updateWhatsAppSettings">Guardando...</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal sucursal --}}
    @if($showBranchModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeBranchModal" class="fixed inset-0 bg-slate-500/75 transition-opacity dark:bg-slate-900/75"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="relative inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:max-w-lg sm:align-middle">
                    <form wire:submit="saveBranch">
                        <div class="px-6 pb-4 pt-5">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ $editingBranchId ? 'Editar sucursal' : 'Nueva sucursal' }}
                            </h3>

                            <div class="mt-4 space-y-4">
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label for="branch_code" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                            Código <span class="text-red-500">*</span>
                                        </label>
                                        <input wire:model="branch_code" type="text" id="branch_code" maxlength="3"
                                               placeholder="001"
                                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                        @error('branch_code')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="branch_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                            Nombre <span class="text-red-500">*</span>
                                        </label>
                                        <input wire:model="branch_name" type="text" id="branch_name"
                                               placeholder="Nombre de la sucursal"
                                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                        @error('branch_name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="branch_address" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Dirección <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model="branch_address" type="text" id="branch_address"
                                           placeholder="Dirección de la sucursal"
                                           class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                    @error('branch_address')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="$toggle('branch_is_main')"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 {{ $branch_is_main ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                            role="switch" aria-checked="{{ $branch_is_main ? 'true' : 'false' }}">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $branch_is_main ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Marcar como sucursal principal</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 bg-slate-50 px-6 py-4 dark:bg-slate-900/50">
                            <button wire:click="closeBranchModal" type="button"
                                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700">
                                <svg wire:loading wire:target="saveBranch" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="saveBranch">{{ $editingBranchId ? 'Actualizar' : 'Crear' }}</span>
                                <span wire:loading wire:target="saveBranch">Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal secuenciales --}}
    @if($showSequentialModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="sequential-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeSequentialModal" class="fixed inset-0 bg-slate-500/75 transition-opacity dark:bg-slate-900/75"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="relative inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:max-w-lg sm:align-middle">
                    <div class="px-6 pb-4 pt-5">
                        <h3 id="sequential-modal-title" class="text-lg font-semibold text-slate-900 dark:text-white">
                            Secuenciales - {{ $editingEmissionPointName }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Establece el último número usado. El siguiente documento generado usará el número siguiente.
                        </p>

                        <div class="mt-5 space-y-3">
                            @foreach(\App\Enums\DocumentType::cases() as $docType)
                                <div>
                                    <label for="seq_{{ $docType->value }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ $docType->label() }}
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input wire:model="sequentialNumbers.{{ $docType->value }}"
                                               type="number" min="0" step="1"
                                               id="seq_{{ $docType->value }}"
                                               class="block w-full rounded-xl border-0 py-2.5 px-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500">
                                        <span class="whitespace-nowrap text-xs text-slate-400 dark:text-slate-500">
                                            Sig: {{ ($sequentialNumbers[$docType->value] ?? 0) + 1 }}
                                        </span>
                                    </div>
                                    @error('sequentialNumbers.' . $docType->value)
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        {{-- Advertencia si baja un secuencial --}}
                        <div class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800">
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <span>Reducir un secuencial por debajo de documentos ya emitidos puede causar duplicados en el SRI.</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 bg-slate-50 px-6 py-4 dark:bg-slate-900/50">
                        <button wire:click="closeSequentialModal" type="button"
                                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                            Cancelar
                        </button>
                        <button wire:click="saveSequentialNumbers" type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700">
                            <svg wire:loading wire:target="saveSequentialNumbers" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveSequentialNumbers">Guardar</span>
                            <span wire:loading wire:target="saveSequentialNumbers">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
