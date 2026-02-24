<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Plantillas de Contabilizacion
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Define reglas de mapeo automatico de documentos a asientos contables
            </p>
        </div>
        <button wire:click="openForm" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva plantilla
        </button>
    </div>

    {{-- Form Modal --}}
    @if($showForm)
        <div class="card p-5">
            <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">
                {{ $editingId ? 'Editar Plantilla' : 'Nueva Plantilla' }}
            </h2>
            <form wire:submit="save" class="space-y-5">
                {{-- Template Info --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label">Tipo de documento</label>
                        <select wire:model="document_type" class="input w-full">
                            <option value="">Seleccionar...</option>
                            @foreach($documentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('document_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nombre de la plantilla</label>
                        <input wire:model="template_name" type="text" class="input w-full" placeholder="Factura de venta estandar">
                        @error('template_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm">
                            <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800">
                            Plantilla activa
                        </label>
                    </div>
                </div>

                {{-- Mapping Rules --}}
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Reglas de Mapeo</h3>
                        <button type="button" wire:click="addRule" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Agregar regla
                        </button>
                    </div>

                    @error('rules_data') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        {{-- Header --}}
                        <div class="hidden grid-cols-12 gap-3 text-xs font-medium text-slate-500 dark:text-slate-400 sm:grid">
                            <div class="col-span-4">Cuenta contable</div>
                            <div class="col-span-2">Lado</div>
                            <div class="col-span-3">Campo de monto</div>
                            <div class="col-span-2">Descripcion</div>
                            <div class="col-span-1"></div>
                        </div>

                        @foreach($rules_data as $index => $rule)
                            <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-12 sm:border-0 sm:p-0" wire:key="rule-{{ $index }}">
                                {{-- Account --}}
                                <div class="sm:col-span-4">
                                    <label class="form-label sm:hidden">Cuenta</label>
                                    <select wire:model="rules_data.{{ $index }}.account_code" class="input w-full text-sm">
                                        <option value="">Seleccionar cuenta...</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->code }}">{{ $account->code }} - {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    @error("rules_data.{$index}.account_code") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>

                                {{-- Side --}}
                                <div class="sm:col-span-2">
                                    <label class="form-label sm:hidden">Lado</label>
                                    <select wire:model="rules_data.{{ $index }}.side" class="input w-full text-sm">
                                        <option value="debit">Debe</option>
                                        <option value="credit">Haber</option>
                                    </select>
                                    @error("rules_data.{$index}.side") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>

                                {{-- Amount Field --}}
                                <div class="sm:col-span-3">
                                    <label class="form-label sm:hidden">Campo de monto</label>
                                    <select wire:model="rules_data.{{ $index }}.amount_field" class="input w-full text-sm">
                                        <option value="subtotal">Subtotal</option>
                                        <option value="subtotal_0">Subtotal 0%</option>
                                        <option value="subtotal_12">Subtotal 12%</option>
                                        <option value="subtotal_15">Subtotal 15%</option>
                                        <option value="total_tax">IVA</option>
                                        <option value="total">Total</option>
                                        <option value="total_discount">Descuento</option>
                                        <option value="retained_value">Valor retenido</option>
                                    </select>
                                    @error("rules_data.{$index}.amount_field") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>

                                {{-- Description --}}
                                <div class="sm:col-span-2">
                                    <label class="form-label sm:hidden">Descripcion</label>
                                    <input wire:model="rules_data.{{ $index }}.description" type="text" class="input w-full text-sm" placeholder="Opcional">
                                </div>

                                {{-- Remove --}}
                                <div class="flex items-start sm:col-span-1">
                                    <button type="button" wire:click="removeRule({{ $index }})"
                                            class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar regla">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <button type="submit" class="btn-primary text-sm">
                        {{ $editingId ? 'Actualizar plantilla' : 'Crear plantilla' }}
                    </button>
                    <button type="button" wire:click="closeForm" class="btn-ghost text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Templates List --}}
    <div class="card overflow-hidden">
        @if($templates->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">No hay plantillas</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea tu primera plantilla de contabilizacion automatica.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tipo de documento</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 text-center font-medium">Reglas</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($templates as $template)
                            <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3">
                                    <span class="badge badge-blue">
                                        {{ $documentTypes[$template->document_type] ?? $template->document_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-slate-900 dark:text-white">{{ $template->name }}</span>
                                    @if($template->mapping_rules)
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach(array_slice($template->mapping_rules, 0, 3) as $rule)
                                                <span class="inline-flex items-center gap-0.5 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                    {{ $rule['account_code'] ?? '?' }}
                                                    <span class="{{ ($rule['side'] ?? '') === 'debit' ? 'text-blue-500' : 'text-emerald-500' }}">
                                                        {{ ($rule['side'] ?? '') === 'debit' ? 'D' : 'H' }}
                                                    </span>
                                                </span>
                                            @endforeach
                                            @if(count($template->mapping_rules) > 3)
                                                <span class="text-[10px] text-slate-400">+{{ count($template->mapping_rules) - 3 }} mas</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="tabular-nums">{{ count($template->mapping_rules ?? []) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <button wire:click="toggleActive({{ $template->id }})"
                                            class="badge badge-{{ $template->is_active ? 'green' : 'gray' }} cursor-pointer">
                                        {{ $template->is_active ? 'Activa' : 'Inactiva' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button wire:click="openForm({{ $template->id }})" class="btn-icon-sm" title="Editar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button wire:click="delete({{ $template->id }})"
                                                wire:confirm="Eliminar esta plantilla?"
                                                class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
