<div class="min-h-screen bg-slate-50 dark:bg-slate-950" x-data="{ showPassword: false }">
    {{-- Top bar with logo --}}
    <div class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/amelogo_v3_optimized.webp') }}" alt="AmePhia" class="h-8 object-contain">
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Configuracion inicial</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                    Cerrar sesion
                </button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Step indicator --}}
        <nav class="mb-10">
            <ol class="flex items-center justify-center">
                @php
                    $stepLabels = [
                        1 => 'Empresa',
                        2 => 'Certificado',
                        3 => 'Establecimiento',
                        4 => 'Cliente',
                        5 => 'Plan',
                        6 => 'Listo',
                    ];
                @endphp
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <li class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center relative">
                            {{-- Circle --}}
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-all duration-300
                                @if ($i < $currentStep)
                                    bg-primary-600 text-white shadow-md shadow-primary-600/25
                                @elseif ($i === $currentStep)
                                    bg-primary-600 text-white ring-4 ring-primary-100 dark:ring-primary-900/50 shadow-md shadow-primary-600/25
                                @else
                                    bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400
                                @endif
                            ">
                                @if ($i < $currentStep)
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                @else
                                    {{ $i }}
                                @endif
                            </div>
                            {{-- Label (hidden on mobile for space) --}}
                            <span class="mt-2 hidden text-xs font-medium sm:block
                                @if ($i <= $currentStep) text-primary-600 dark:text-primary-400
                                @else text-slate-400 dark:text-slate-500
                                @endif
                            ">
                                {{ $stepLabels[$i] ?? '' }}
                            </span>
                        </div>
                        {{-- Connector line --}}
                        @if ($i < $totalSteps)
                            <div class="mx-2 h-0.5 flex-1 rounded-full transition-all duration-300 sm:mx-4
                                {{ $i < $currentStep ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700' }}
                            "></div>
                        @endif
                    </li>
                @endfor
            </ol>
        </nav>

        {{-- ============================================================ --}}
        {{-- STEP 1: Company Info --}}
        {{-- ============================================================ --}}
        @if ($currentStep === 1)
            <div class="card">
                <div class="card-body">
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-900/30">
                                <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M3.75 3v18m16.5-18v18M5.25 3h13.5M5.25 21V6h13.5v15" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Datos de la empresa</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Ingresa la informacion de tu empresa tal como aparece en el RUC.</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="saveCompany" class="space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- RUC --}}
                            <div class="form-group">
                                <label for="ruc" class="form-label">
                                    RUC <span class="text-danger-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input wire:model.blur="ruc" type="text" id="ruc" maxlength="13"
                                           placeholder="0102030405001"
                                           class="form-input tabular-nums flex-1">
                                    <button type="button" wire:click="lookupRuc" wire:loading.attr="disabled" wire:target="lookupRuc, ruc"
                                            class="btn-secondary shrink-0 whitespace-nowrap">
                                        <span wire:loading.remove wire:target="lookupRuc, ruc">Consultar SRI</span>
                                        <span wire:loading wire:target="lookupRuc, ruc">Consultando...</span>
                                    </button>
                                </div>
                                @error('ruc') <p class="form-error">{{ $message }}</p> @enderror
                                @if ($rucLookupStatus)
                                    <p class="mt-1 text-xs {{ $rucLookupStatus === 'ACTIVO' ? 'text-success-600 dark:text-success-400' : 'text-danger-500' }}">
                                        Estado en el SRI: {{ $rucLookupStatus }}
                                        @if ($rucLookupStatus !== 'ACTIVO')
                                            — verifica tu RUC antes de emitir comprobantes.
                                        @endif
                                    </p>
                                @endif
                            </div>

                            {{-- Razon Social --}}
                            <div class="form-group">
                                <label for="business_name" class="form-label">
                                    Razon social <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="business_name" type="text" id="business_name"
                                       placeholder="Mi Empresa S.A."
                                       class="form-input">
                                @error('business_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Nombre Comercial --}}
                        <div class="form-group">
                            <label for="trade_name" class="form-label">Nombre comercial</label>
                            <input wire:model="trade_name" type="text" id="trade_name"
                                   placeholder="Nombre comercial (opcional)"
                                   class="form-input">
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Si no se especifica, se usara la razon social.</p>
                        </div>

                        {{-- Direccion --}}
                        <div class="form-group">
                            <label for="address" class="form-label">
                                Direccion matriz <span class="text-danger-500">*</span>
                            </label>
                            <input wire:model="address" type="text" id="address"
                                   placeholder="Av. Principal y Calle Secundaria"
                                   class="form-input">
                            @error('address') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="form-group">
                                <label for="company_email" class="form-label">
                                    Correo del emisor <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="email" type="email" id="company_email"
                                       placeholder="empresa@ejemplo.com"
                                       class="form-input">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label for="sri_password" class="form-label">
                                    Clave SRI <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="sri_password" type="password" id="sri_password"
                                       autocomplete="new-password"
                                       placeholder="{{ $sriPasswordConfigured ? '******** (dejar vacio para conservarla)' : 'Ingresa la clave del SRI' }}"
                                       class="form-input">
                                @if ($sriPasswordConfigured)
                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Ya existe una clave guardada. Solo completa este campo si deseas reemplazarla.</p>
                                @endif
                                @error('sri_password') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Ambiente SRI --}}
                        <div class="form-group">
                            <label class="form-label">Ambiente SRI <span class="text-danger-500">*</span></label>
                            <div class="mt-2 flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model="sri_environment" type="radio" value="1"
                                           class="h-4 w-4 border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">
                                        <span class="font-medium">Pruebas</span>
                                        <span class="text-slate-400"> &mdash; para testing</span>
                                    </span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model="sri_environment" type="radio" value="2"
                                           class="h-4 w-4 border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">
                                        <span class="font-medium">Produccion</span>
                                        <span class="text-slate-400"> &mdash; documentos reales</span>
                                    </span>
                                </label>
                            </div>
                            @error('sri_environment') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Tipo de Contribuyente --}}
                            <div class="form-group">
                                <label for="taxpayer_type" class="form-label">Tipo de contribuyente</label>
                                <select wire:model="taxpayer_type" id="taxpayer_type" class="form-input">
                                    <option value="natural">Persona Natural</option>
                                    <option value="juridica">Sociedad / Persona Juridica</option>
                                    <option value="rise">RISE</option>
                                    <option value="rimpe_emprendedor">RIMPE Emprendedor</option>
                                    <option value="rimpe_negocio_popular">RIMPE Negocio Popular</option>
                                </select>
                            </div>

                            {{-- Obligado a Contabilidad --}}
                            <div class="form-group flex items-end pb-1">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input wire:model="obligated_accounting" type="checkbox"
                                           class="h-5 w-5 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Obligado a llevar contabilidad
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit"
                                    class="btn-primary inline-flex items-center gap-2"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveCompany">Continuar</span>
                                <span wire:loading wire:target="saveCompany" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Guardando...
                                </span>
                                <svg wire:loading.remove wire:target="saveCompany" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- STEP 2: Certificate --}}
        {{-- ============================================================ --}}
        @if ($currentStep === 2)
            <div class="card">
                <div class="card-body">
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Firma electronica</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Sube tu certificado digital (.p12) para firmar documentos electronicos.</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="saveCertificate" class="space-y-5">
                        {{-- Drag & Drop Area --}}
                        <div class="form-group">
                            <label class="form-label">Archivo del certificado (.p12) <span class="text-danger-500">*</span></label>
                            <div class="mt-1"
                                 x-data="{ isDragging: false }"
                                 x-on:dragover.prevent="isDragging = true"
                                 x-on:dragleave.prevent="isDragging = false"
                                 x-on:drop.prevent="isDragging = false">
                                <label for="certificate"
                                       class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition-all duration-200"
                                       :class="isDragging
                                           ? 'border-primary-400 bg-primary-50 dark:border-primary-500 dark:bg-primary-900/20'
                                           : 'border-slate-300 bg-slate-50 hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-primary-700'">
                                    @if ($certificate)
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30">
                                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $certificate->getClientOriginalName() }}</p>
                                                <p class="text-xs text-slate-500">{{ number_format($certificate->getSize() / 1024, 1) }} KB</p>
                                            </div>
                                        </div>
                                    @else
                                        <svg class="mb-3 h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                        </svg>
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            Arrastra tu certificado aqui o <span class="text-primary-600 dark:text-primary-400">selecciona un archivo</span>
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">Archivo .p12 o .pfx (max. 5MB)</p>
                                    @endif
                                    <input wire:model="certificate" type="file" id="certificate" class="sr-only" accept=".p12,.pfx">
                                </label>
                            </div>
                            @error('certificate') <p class="form-error">{{ $message }}</p> @enderror

                            {{-- Upload progress --}}
                            <div wire:loading wire:target="certificate" class="mt-2">
                                <div class="flex items-center gap-2 text-sm text-primary-600 dark:text-primary-400">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Cargando archivo...
                                </div>
                            </div>
                        </div>

                        {{-- Certificate Password --}}
                        <div class="form-group">
                            <label for="certificate_password" class="form-label">
                                Contrasena del certificado <span class="text-danger-500">*</span>
                            </label>
                            <div class="relative">
                                <input wire:model="certificate_password"
                                       :type="showPassword ? 'text' : 'password'"
                                       id="certificate_password"
                                       placeholder="Ingresa la contrasena del .p12"
                                       class="form-input pr-10">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('certificate_password') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Info box --}}
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                            <div class="flex gap-3">
                                <svg class="h-5 w-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                <div class="text-sm text-blue-700 dark:text-blue-300">
                                    <p class="font-medium">Tu certificado se almacena de forma segura</p>
                                    <p class="mt-1 text-blue-600 dark:text-blue-400">La contrasena se encripta y nunca se muestra en texto plano. Puedes obtener tu firma electronica en <a href="https://www.securitydata.net.ec" target="_blank" class="underline hover:no-underline">SecurityData</a>, <a href="https://www.uanataca.ec" target="_blank" class="underline hover:no-underline">Uanataca</a> o el <a href="https://www.registrocivil.gob.ec" target="_blank" class="underline hover:no-underline">Registro Civil</a>.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" wire:click="previousStep" class="btn-ghost inline-flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                </svg>
                                Atras
                            </button>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                    Paso obligatorio
                                </span>
                                <button type="submit"
                                        class="btn-primary inline-flex items-center gap-2"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveCertificate">Subir certificado</span>
                                    <span wire:loading wire:target="saveCertificate" class="inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Verificando...
                                    </span>
                                    <svg wire:loading.remove wire:target="saveCertificate" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- STEP 3: Branch + Emission Point --}}
        {{-- ============================================================ --}}
        @if ($currentStep === 3)
            <div class="card">
                <div class="card-body">
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Establecimiento y punto de emision</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Configura tu establecimiento principal (sucursal) y su punto de emision.</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="saveBranch" class="space-y-5">
                        {{-- Branch section --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Establecimiento</h3>
                            <div class="grid gap-5 sm:grid-cols-3">
                                <div class="form-group">
                                    <label for="branch_code" class="form-label">
                                        Codigo <span class="text-danger-500">*</span>
                                    </label>
                                    <input wire:model="branch_code" type="text" id="branch_code" maxlength="3"
                                           placeholder="001"
                                           class="form-input tabular-nums font-mono text-center text-lg">
                                    @error('branch_code') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="form-group sm:col-span-2">
                                    <label for="branch_name" class="form-label">
                                        Nombre <span class="text-danger-500">*</span>
                                    </label>
                                    <input wire:model="branch_name" type="text" id="branch_name"
                                           placeholder="Matriz"
                                           class="form-input">
                                    @error('branch_name') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-4 form-group">
                                <label for="branch_address" class="form-label">
                                    Direccion <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="branch_address" type="text" id="branch_address"
                                       placeholder="Direccion del establecimiento"
                                       class="form-input">
                                @error('branch_address') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Emission Point section --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Punto de emision</h3>
                            <div class="form-group">
                                <label for="ep_code" class="form-label">
                                    Codigo <span class="text-danger-500">*</span>
                                </label>
                                <div class="max-w-[160px]">
                                    <input wire:model="ep_code" type="text" id="ep_code" maxlength="3"
                                           placeholder="001"
                                           class="form-input tabular-nums font-mono text-center text-lg">
                                </div>
                                @error('ep_code') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Generalmente es 001 para el primer punto de emision.</p>
                            </div>
                        </div>

                        {{-- Info box --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                            <div class="flex gap-3">
                                <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Los numeros secuenciales para facturas, notas de credito, debito, retenciones y guias de remision se inicializaran automaticamente.
                                </p>
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" wire:click="previousStep" class="btn-ghost inline-flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                </svg>
                                Atras
                            </button>
                            <button type="submit"
                                    class="btn-primary inline-flex items-center gap-2"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveBranch">Continuar</span>
                                <span wire:loading wire:target="saveBranch" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Guardando...
                                </span>
                                <svg wire:loading.remove wire:target="saveBranch" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- STEP 4: First Customer (Optional) --}}
        {{-- ============================================================ --}}
        @if ($currentStep === 4)
            <div class="card">
                <div class="card-body">
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/30">
                                <svg class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Registra tu primer cliente</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Agrega un cliente para poder crear tu primera factura. Este paso es opcional.</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="saveCustomer" class="space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Tipo de Identificacion --}}
                            <div class="form-group">
                                <label for="customer_identification_type" class="form-label">
                                    Tipo de identificacion <span class="text-danger-500">*</span>
                                </label>
                                <select wire:model="customer_identification_type" id="customer_identification_type" class="form-input">
                                    <option value="04">RUC</option>
                                    <option value="05">Cedula</option>
                                    <option value="06">Pasaporte</option>
                                    <option value="07">Consumidor Final</option>
                                    <option value="08">Identificacion del Exterior</option>
                                </select>
                                @error('customer_identification_type') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Numero de Identificacion --}}
                            <div class="form-group">
                                <label for="customer_identification" class="form-label">
                                    Numero de identificacion <span class="text-danger-500">*</span>
                                </label>
                                <input wire:model="customer_identification" type="text" id="customer_identification"
                                       placeholder="{{ $customer_identification_type === '04' ? '0102030405001' : '0102030405' }}"
                                       class="form-input tabular-nums">
                                @error('customer_identification') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Nombre / Razon Social --}}
                        <div class="form-group">
                            <label for="customer_name" class="form-label">
                                Nombre / Razon social <span class="text-danger-500">*</span>
                            </label>
                            <input wire:model="customer_name" type="text" id="customer_name"
                                   placeholder="Nombre completo o razon social"
                                   class="form-input">
                            @error('customer_name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="customer_email" class="form-label">Correo electronico</label>
                            <input wire:model="customer_email" type="email" id="customer_email"
                                   placeholder="cliente@ejemplo.com"
                                   class="form-input">
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Se usara para enviar comprobantes electronicos.</p>
                            @error('customer_email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" wire:click="previousStep" class="btn-ghost inline-flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                </svg>
                                Atras
                            </button>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="skipCustomer"
                                        class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                                    Omitir por ahora
                                </button>
                                <button type="submit"
                                        class="btn-primary inline-flex items-center gap-2"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveCustomer">Guardar cliente</span>
                                    <span wire:loading wire:target="saveCustomer" class="inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Guardando...
                                    </span>
                                    <svg wire:loading.remove wire:target="saveCustomer" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- STEP 5: Plan Selection --}}
        {{-- ============================================================ --}}
        @if ($currentStep === 5)
            <div class="card">
                <div class="card-body">
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-100 dark:bg-teal-900/30">
                                <svg class="h-5 w-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Elige tu plan</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Selecciona el plan que mejor se adapte a las necesidades de tu negocio.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Billing cycle toggle --}}
                    <div class="mb-8 flex items-center justify-center">
                        <div class="inline-flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800" x-data>
                            <button type="button"
                                    wire:click="$set('billingCycle', 'monthly')"
                                    class="rounded-lg px-5 py-2.5 text-sm font-semibold transition-all duration-200
                                        {{ $billingCycle === 'monthly'
                                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                                            : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                                Mensual
                            </button>
                            <button type="button"
                                    wire:click="$set('billingCycle', 'yearly')"
                                    class="rounded-lg px-5 py-2.5 text-sm font-semibold transition-all duration-200 inline-flex items-center gap-2
                                        {{ $billingCycle === 'yearly'
                                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                                            : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                                Anual
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">-20%</span>
                            </button>
                        </div>
                    </div>

                    {{-- Plan cards --}}
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->plans as $plan)
                            @php
                                $isSelected = $selectedPlanId === $plan->id;
                                $price = $billingCycle === 'yearly' && $plan->price_yearly
                                    ? $plan->price_yearly / 12
                                    : $plan->price_monthly;
                                $totalPrice = $billingCycle === 'yearly' && $plan->price_yearly
                                    ? $plan->price_yearly
                                    : $plan->price_monthly;
                                $features = $plan->getFeaturesList();
                            @endphp
                            <div wire:click="selectPlan({{ $plan->id }})"
                                 class="relative cursor-pointer rounded-2xl bg-white p-6 shadow-sm ring-1 transition-all duration-200 dark:bg-slate-800
                                    {{ $isSelected
                                        ? 'ring-2 ring-teal-600 shadow-lg shadow-teal-500/10 dark:ring-teal-500'
                                        : 'ring-slate-900/5 hover:shadow-md hover:ring-slate-900/10 dark:ring-white/10 dark:hover:ring-white/20' }}">

                                {{-- Featured badge --}}
                                @if ($plan->is_featured)
                                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-teal-600 px-3 py-1 text-xs font-semibold text-white shadow-lg shadow-teal-500/25">
                                        Popular
                                    </div>
                                @endif

                                {{-- Selected indicator --}}
                                @if ($isSelected)
                                    <div class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full bg-teal-600 text-white">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="text-center">
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h4>
                                    @if ($plan->description)
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>
                                    @endif

                                    <div class="mt-4">
                                        @if ($plan->isFree())
                                            <span class="text-4xl font-bold text-slate-900 dark:text-white">Gratis</span>
                                        @else
                                            <span class="text-4xl font-bold text-slate-900 dark:text-white">${{ number_format($price, 2) }}</span>
                                            <span class="text-sm text-slate-500">/mes</span>
                                        @endif
                                    </div>

                                    @if (!$plan->isFree() && $billingCycle === 'yearly' && $plan->price_yearly)
                                        @php
                                            $savings = $plan->getYearlySavingsPercent();
                                        @endphp
                                        <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                            ${{ number_format($plan->price_yearly, 2) }}/ano
                                            @if ($savings > 0)
                                                (ahorra {{ $savings }}%)
                                            @endif
                                        </p>
                                    @elseif (!$plan->isFree() && $billingCycle === 'monthly')
                                        <p class="mt-1 text-sm text-slate-400 dark:text-slate-500">
                                            Facturacion mensual
                                        </p>
                                    @endif
                                </div>

                                {{-- Features list --}}
                                <ul class="mt-6 space-y-2.5">
                                    @foreach (array_slice($features, 0, 7) as $feature)
                                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                    @if (count($features) > 7)
                                        <li class="text-xs font-medium text-slate-400 dark:text-slate-500 pl-6">
                                            + {{ count($features) - 7 }} caracteristicas mas
                                        </li>
                                    @endif
                                </ul>

                                {{-- Select indicator at bottom --}}
                                <div class="mt-6">
                                    <div class="w-full rounded-xl px-4 py-2.5 text-center text-sm font-semibold transition-all duration-200
                                        {{ $isSelected
                                            ? 'bg-teal-600 text-white shadow-lg shadow-teal-500/25'
                                            : ($plan->is_featured
                                                ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-400'
                                                : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300') }}">
                                        {{ $isSelected ? 'Seleccionado' : 'Seleccionar plan' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('selectedPlanId') <p class="mt-4 text-center form-error">{{ $message }}</p> @enderror

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between pt-6 mt-6 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="previousStep" class="btn-ghost inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Atras
                        </button>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium uppercase tracking-wider text-teal-600 dark:text-teal-400">
                                Seleccion requerida
                            </span>
                            <button type="button"
                                    wire:click="savePlan"
                                    class="btn-primary inline-flex items-center gap-2"
                                    wire:loading.attr="disabled"
                                    {{ !$selectedPlanId ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="savePlan">Continuar</span>
                                <span wire:loading wire:target="savePlan" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Guardando...
                                </span>
                                <svg wire:loading.remove wire:target="savePlan" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- STEP 6: Completion --}}
        {{-- ============================================================ --}}
        @if ($currentStep === 6)
            <div class="card">
                <div class="card-body py-12">
                    <div class="mx-auto max-w-2xl text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full {{ $this->canCompleteOnboarding ? 'bg-green-100 dark:bg-green-900/30' : 'bg-amber-100 dark:bg-amber-900/30' }}">
                            <svg class="h-10 w-10 {{ $this->canCompleteOnboarding ? 'text-green-600 dark:text-green-400' : 'text-amber-500 dark:text-amber-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                            </svg>
                        </div>

                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Validacion final del emisor</h2>
                        <p class="mt-2 text-slate-500 dark:text-slate-400">
                            Antes de entrar al panel, la cuenta debe quedar lista para operar y emitir documentos sin configuraciones pendientes.
                        </p>
                    </div>

                    @error('onboarding')
                        <div class="mx-auto mt-6 max-w-2xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="mx-auto mt-8 max-w-2xl space-y-3">
                        @foreach ($this->requiredSetupItems as $item)
                            <div class="flex items-start gap-4 rounded-xl border px-4 py-4 {{ $item['ready'] ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-900/15' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40' }}">
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $item['ready'] ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                                    @if ($item['ready'])
                                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4 text-white/80" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <button type="button" wire:click="previousStep" class="btn-ghost inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Revisar configuracion
                        </button>

                        <a href="{{ route('panel.settings.company') }}"
                           class="btn-ghost inline-flex items-center gap-2">
                            Ajustes avanzados
                        </a>

                        <button wire:click="completeOnboarding"
                                class="btn-primary inline-flex items-center gap-2 px-8"
                                wire:loading.attr="disabled"
                                {{ !$this->canCompleteOnboarding ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="completeOnboarding">Entrar al sistema</span>
                            <span wire:loading wire:target="completeOnboarding" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Validando...
                            </span>
                            <svg wire:loading.remove wire:target="completeOnboarding" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
