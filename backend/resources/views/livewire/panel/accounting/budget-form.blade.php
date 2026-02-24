<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('panel.accounting.budgets') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400" wire:navigate>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver a presupuestos
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $budget ? 'Editar Presupuesto' : 'Nuevo Presupuesto' }}
            </h1>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- General Info --}}
        <div class="card p-5">
            <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Informacion General</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="form-label">Nombre del presupuesto</label>
                    <input wire:model="name" type="text" class="input w-full" placeholder="Presupuesto operativo 2025">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Ano</label>
                    <select wire:model="year" class="input w-full">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    @error('year') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Notas</label>
                    <input wire:model="notes" type="text" class="input w-full" placeholder="Notas opcionales...">
                    @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Budget Lines --}}
        <div class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Lineas del Presupuesto</h2>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Total: <span class="tabular-nums text-primary-600 dark:text-primary-400">${{ number_format($totalAmount, 2) }}</span>
                    </span>
                    <button type="button" wire:click="addLine" class="btn-secondary text-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Agregar linea
                    </button>
                </div>
            </div>

            @error('lines') <p class="mb-3 text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="space-y-3">
                {{-- Header --}}
                <div class="hidden grid-cols-12 gap-3 text-xs font-medium text-slate-500 dark:text-slate-400 sm:grid">
                    <div class="col-span-5">Cuenta contable</div>
                    <div class="col-span-2">Mes</div>
                    <div class="col-span-3">Monto presupuestado</div>
                    <div class="col-span-2">Acciones</div>
                </div>

                @foreach($lines as $index => $line)
                    <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-12 sm:border-0 sm:p-0" wire:key="line-{{ $index }}">
                        {{-- Account --}}
                        <div class="sm:col-span-5">
                            <label class="form-label sm:hidden">Cuenta contable</label>
                            <select wire:model="lines.{{ $index }}.account_id" class="input w-full text-sm">
                                <option value="">Seleccionar cuenta...</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error("lines.{$index}.account_id") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Month --}}
                        <div class="sm:col-span-2">
                            <label class="form-label sm:hidden">Mes</label>
                            <select wire:model="lines.{{ $index }}.month" class="input w-full text-sm">
                                @foreach($months as $num => $monthName)
                                    <option value="{{ $num }}">{{ $monthName }}</option>
                                @endforeach
                            </select>
                            @error("lines.{$index}.month") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Amount --}}
                        <div class="sm:col-span-3">
                            <label class="form-label sm:hidden">Monto</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">$</span>
                                <input wire:model.lazy="lines.{{ $index }}.budgeted_amount" type="number" step="0.01" min="0"
                                       class="input w-full pl-7 text-sm tabular-nums" placeholder="0.00">
                            </div>
                            @error("lines.{$index}.budgeted_amount") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-start gap-1 sm:col-span-2">
                            <button type="button" wire:click="duplicateLine({{ $index }})"
                                    class="btn-icon-sm" title="Duplicar linea">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                </svg>
                            </button>
                            <button type="button" wire:click="removeLine({{ $index }})"
                                    class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar linea">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                <button type="button" wire:click="addLine" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar otra linea
                </button>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('panel.accounting.budgets') }}" class="btn-ghost" wire:navigate>Cancelar</a>
            <button type="submit" class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                {{ $budget ? 'Actualizar presupuesto' : 'Crear presupuesto' }}
            </button>
        </div>
    </form>
</div>
