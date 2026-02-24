<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Configuracion Contable
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Parametros generales del modulo de contabilidad
            </p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Accounting Standard --}}
        <div class="card p-5">
            <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">Estandar Contable</h2>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                Selecciona el marco normativo contable para tu empresa.
                @if($hasAccounts)
                    <span class="font-medium text-amber-600 dark:text-amber-400">No se puede cambiar una vez creadas las cuentas.</span>
                @endif
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($standards as $standard)
                    <label class="relative flex cursor-pointer rounded-lg border-2 p-4 transition-colors
                        {{ $accounting_standard === $standard['value']
                            ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950/30'
                            : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600' }}
                        {{ $hasAccounts ? 'opacity-75 cursor-not-allowed' : '' }}">
                        <input wire:model="accounting_standard" type="radio" name="accounting_standard"
                               value="{{ $standard['value'] }}"
                               class="sr-only"
                               {{ $hasAccounts ? 'disabled' : '' }}>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg
                                {{ $accounting_standard === $standard['value']
                                    ? 'bg-primary-500 text-white'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $standard['label'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    @if($standard['value'] === 'niif_full')
                                        Para sociedades y grandes empresas
                                    @else
                                        Para pequenas y medianas empresas
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($accounting_standard === $standard['value'])
                            <div class="absolute right-3 top-3">
                                <svg class="h-5 w-5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </label>
                @endforeach
            </div>
            @error('accounting_standard') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Feature Toggles --}}
        <div class="card p-5">
            <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">Funcionalidades</h2>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                Activa o desactiva funcionalidades del modulo contable segun las necesidades de tu empresa.
            </p>

            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                {{-- Auto Journal Entries --}}
                <div class="flex items-center justify-between py-4">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Asientos automaticos</h3>
                        </div>
                        <p class="mt-1 ml-10 text-sm text-slate-500 dark:text-slate-400">
                            Genera asientos contables automaticamente al autorizar facturas, compras y retenciones.
                        </p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input wire:model="auto_journal_entries" type="checkbox" class="peer sr-only">
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 dark:bg-slate-700 dark:peer-focus:ring-primary-800"></div>
                    </label>
                </div>

                {{-- Cost Centers --}}
                <div class="flex items-center justify-between py-4">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/50 dark:text-violet-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Centros de costo</h3>
                        </div>
                        <p class="mt-1 ml-10 text-sm text-slate-500 dark:text-slate-400">
                            Permite asignar centros de costo a las lineas de asientos contables para analisis de gastos.
                        </p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input wire:model="cost_centers_enabled" type="checkbox" class="peer sr-only">
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 dark:bg-slate-700 dark:peer-focus:ring-primary-800"></div>
                    </label>
                </div>

                {{-- Budgets --}}
                <div class="flex items-center justify-between py-4">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Presupuestos</h3>
                        </div>
                        <p class="mt-1 ml-10 text-sm text-slate-500 dark:text-slate-400">
                            Habilita la creacion de presupuestos anuales y el seguimiento de la ejecucion presupuestaria.
                        </p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input wire:model="budgets_enabled" type="checkbox" class="peer sr-only">
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 dark:bg-slate-700 dark:peer-focus:ring-primary-800"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Guardar configuracion
            </button>
        </div>
    </form>
</div>
