<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ $entryId ? 'Editar Asiento Contable' : 'Nuevo Asiento Contable' }}
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                {{ $entryId ? 'Modificar asiento en borrador' : 'Registrar un nuevo asiento en el libro diario' }}
            </p>
        </div>
        <a href="{{ route('panel.accounting.journal-entries') }}" class="btn-secondary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
            </svg>
            Volver a asientos
        </a>
    </div>

    {{-- General Info --}}
    <form wire:submit.prevent="save">
        <div class="card p-5">
            <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Informacion general</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="form-label">Empresa</label>
                    <select wire:model.live="companyId" class="input w-full">
                        <option value="">Seleccionar empresa</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->business_name }}</option>
                        @endforeach
                    </select>
                    @error('companyId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Fecha</label>
                    <input wire:model.live="entryDate" type="date" class="input w-full" />
                    @error('entryDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Descripcion</label>
                    <input wire:model.live="description" type="text" class="input w-full" placeholder="Descripcion del asiento" />
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Lines Table --}}
        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Lineas del asiento</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium" style="min-width: 280px">Cuenta</th>
                            <th class="px-4 py-3 font-medium text-right" style="min-width: 130px">Debito</th>
                            <th class="px-4 py-3 font-medium text-right" style="min-width: 130px">Credito</th>
                            <th class="px-4 py-3 font-medium" style="min-width: 200px">Descripcion</th>
                            <th class="px-4 py-3 font-medium text-center w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($lines as $index => $line)
                            <tr class="group" wire:key="line-{{ $index }}">
                                <td class="px-4 py-2 text-slate-400 text-xs tabular-nums">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">
                                    <div class="relative">
                                        @if($line['account_id'])
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center rounded bg-slate-100 px-2 py-1 text-xs font-mono font-medium text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                                    {{ $line['account_code'] }}
                                                </span>
                                                <span class="text-sm text-slate-900 dark:text-white">{{ $line['account_name'] }}</span>
                                                <button type="button" wire:click="$set('lines.{{ $index }}.account_id', null)" class="ml-1 text-slate-400 hover:text-red-500">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @else
                                            <input type="text"
                                                wire:model.live.debounce.300ms="accountSearch"
                                                wire:focus="searchAccounts({{ $index }})"
                                                wire:keyup="searchAccounts({{ $index }})"
                                                class="input w-full"
                                                placeholder="Buscar cuenta..."
                                                autocomplete="off" />
                                            @if($searchingLineIndex === $index && count($accountResults) > 0)
                                                <div class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-800">
                                                    <ul class="max-h-48 overflow-y-auto py-1">
                                                        @foreach($accountResults as $result)
                                                            <li>
                                                                <button type="button"
                                                                    wire:click="selectAccount({{ $index }}, {{ $result['id'] }})"
                                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                                                                    <span class="font-mono text-xs font-medium text-slate-500">{{ $result['code'] }}</span>
                                                                    <span class="text-slate-900 dark:text-white">{{ $result['name'] }}</span>
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    @error("lines.{$index}.account_id") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" wire:model.live.debounce.500ms="lines.{{ $index }}.debit"
                                        class="input w-full text-right tabular-nums" step="0.01" min="0" placeholder="0.00" />
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" wire:model.live.debounce.500ms="lines.{{ $index }}.credit"
                                        class="input w-full text-right tabular-nums" step="0.01" min="0" placeholder="0.00" />
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" wire:model.live="lines.{{ $index }}.description"
                                        class="input w-full" placeholder="Detalle linea" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    @if(count($lines) > 2)
                                        <button type="button" wire:click="removeLine({{ $index }})"
                                            class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar linea">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-800">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">
                                Totales
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                                ${{ number_format($totalDebit, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                                ${{ number_format($totalCredit, 2) }}
                            </td>
                            <td colspan="2" class="px-4 py-3">
                                @if($isBalanced)
                                    <span class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Balanceado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        Diferencia: ${{ number_format($difference, 2) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                <button type="button" wire:click="addLine" class="btn-ghost text-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Agregar linea
                </button>
                <button type="submit" class="btn-primary" @if(!$isBalanced) disabled @endif>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $entryId ? 'Actualizar asiento' : 'Guardar asiento' }}
                </button>
            </div>
        </div>
    </form>
</div>
