<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Configuracion Contable
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Configure el modulo de contabilidad en 3 sencillos pasos
            </p>
        </div>
    </div>

    {{-- Progress Steps --}}
    <div class="card p-6">
        <div class="flex items-center justify-between">
            @foreach([1 => 'Norma Contable', 2 => 'Plan de Cuentas', 3 => 'Periodos Fiscales'] as $num => $label)
                <div class="flex items-center {{ $num < 3 ? 'flex-1' : '' }}">
                    <div class="flex items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold
                            {{ $step >= $num ? 'bg-primary-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400' }}">
                            @if($step > $num)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="ml-3 text-sm font-medium {{ $step >= $num ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">
                            {{ $label }}
                        </span>
                    </div>
                    @if($num < 3)
                        <div class="mx-4 flex-1 border-t-2 {{ $step > $num ? 'border-primary-500' : 'border-slate-200 dark:border-slate-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Company Selector (if multiple) --}}
    @if($companies->count() > 1)
        <div class="card p-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Empresa</label>
            <select wire:model.live="companyId" class="input w-full sm:w-96">
                <option value="0">Seleccionar empresa...</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->business_name }} ({{ $company->ruc }})</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Step 1: Select NIIF Standard --}}
    @if($step === 1)
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                Seleccione la Norma Contable
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Ecuador adopta las Normas Internacionales de Informacion Financiera (NIIF). Seleccione la version que aplica a su empresa.
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($standards as $standard)
                    <label class="relative flex cursor-pointer rounded-xl border-2 p-6 transition-all
                        {{ $accountingStandard === $standard->value
                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                            : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600' }}">
                        <input type="radio" wire:model="accountingStandard" value="{{ $standard->value }}"
                            class="sr-only" />
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg
                                    {{ $accountingStandard === $standard->value
                                        ? 'bg-primary-100 dark:bg-primary-800'
                                        : 'bg-slate-100 dark:bg-slate-800' }}">
                                    <svg class="h-5 w-5 {{ $accountingStandard === $standard->value ? 'text-primary-600' : 'text-slate-500' }}"
                                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $standard->label() }}
                                    </span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        @if($standard->value === 'niif_full')
                                            Para empresas que reportan al mercado de valores o con activos mayores a $4M
                                        @else
                                            Para pequenas y medianas empresas sin obligacion publica de rendir cuentas
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if($accountingStandard === $standard->value)
                            <div class="absolute right-4 top-4">
                                <svg class="h-5 w-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button wire:click="saveStandard" class="btn-primary" @if(!$companyId) disabled @endif>
                    Continuar
                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Step 2: Seed Chart of Accounts --}}
    @if($step === 2)
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                Plan de Cuentas
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Se creara un plan de cuentas basado en la Superintendencia de Companias del Ecuador conforme a la norma seleccionada.
            </p>

            @if($accountsSeeded)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-800">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-emerald-800 dark:text-emerald-200">Plan de cuentas creado</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ $accountsCount }} cuentas registradas exitosamente.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-800">
                            <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-slate-200">Listo para crear</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Se creara el catalogo completo de cuentas segun {{ $accountingStandard === 'niif_full' ? 'NIIF Completas' : 'NIIF para PYMES' }}.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <button wire:click="previousStep" class="btn-secondary">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Anterior
                </button>
                <div class="flex gap-2">
                    @if($accountsSeeded)
                        <button wire:click="nextStep" class="btn-primary">
                            Continuar
                            <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    @else
                        <button wire:click="seedAccounts" wire:loading.attr="disabled" class="btn-primary">
                            <span wire:loading.remove wire:target="seedAccounts">Crear Plan de Cuentas</span>
                            <span wire:loading wire:target="seedAccounts" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Creando...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Step 3: Create Fiscal Periods --}}
    @if($step === 3)
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                Periodos Fiscales
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Se crearan 12 periodos mensuales y 1 periodo anual para el ano seleccionado.
            </p>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ano Fiscal</label>
                <input type="number" wire:model="year" min="2020" max="2099" class="input w-full sm:w-48" />
            </div>

            @if($periodsCreated)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-800">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-emerald-800 dark:text-emerald-200">Periodos fiscales creados</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ $periodsCount }} periodos creados para el ano {{ $year }}.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <button wire:click="previousStep" class="btn-secondary">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Anterior
                </button>
                <div class="flex gap-2">
                    @if(!$periodsCreated)
                        <button wire:click="createPeriods" wire:loading.attr="disabled" class="btn-primary">
                            <span wire:loading.remove wire:target="createPeriods">Crear Periodos</span>
                            <span wire:loading wire:target="createPeriods" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Creando...
                            </span>
                        </button>
                    @endif
                    @if($periodsCreated)
                        <button wire:click="finish" class="btn-primary">
                            Finalizar e ir al Dashboard
                            <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
