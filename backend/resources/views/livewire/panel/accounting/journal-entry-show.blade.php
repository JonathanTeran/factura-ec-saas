<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Asiento #{{ $entry->entry_number }}
                    </h1>
                    <span class="badge badge-{{ $entry->status->color() }}">
                        {{ $entry->status->label() }}
                    </span>
                </div>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                    {{ $entry->description }}
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('panel.accounting.journal-entries') }}" class="btn-secondary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Volver
            </a>
            @if($entry->status->value === 'draft')
                <a href="{{ route('panel.accounting.journal-entries.edit', $entry->id) }}" class="btn-secondary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Editar
                </a>
                <button wire:click="postEntry" wire:confirm="Contabilizar este asiento? Esta accion no se puede deshacer."
                    class="btn-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Contabilizar
                </button>
            @endif
            @if($entry->status->value === 'posted')
                <button wire:click="openVoidModal" class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 dark:border-red-700 dark:bg-slate-800 dark:text-red-400 dark:hover:bg-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Anular
                </button>
            @endif
        </div>
    </div>

    {{-- General Info --}}
    <div class="card p-5">
        <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Informacion general</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Fecha</dt>
                <dd class="mt-1 text-sm text-slate-900 tabular-nums dark:text-white">{{ $entry->entry_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Empresa</dt>
                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entry->company->business_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Descripcion</dt>
                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entry->description ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Origen</dt>
                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entry->source_type->label() }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Periodo fiscal</dt>
                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entry->fiscalPeriod->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Creado por</dt>
                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entry->createdByUser->name ?? '-' }}</dd>
            </div>
            @if($entry->posted_at)
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Contabilizado por</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                        {{ $entry->postedByUser->name ?? '-' }}
                        <span class="text-xs text-slate-400 tabular-nums">({{ $entry->posted_at->format('d/m/Y H:i') }})</span>
                    </dd>
                </div>
            @endif
            @if($entry->voided_at)
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Anulado por</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                        {{ $entry->voidedByUser->name ?? '-' }}
                        <span class="text-xs text-slate-400 tabular-nums">({{ $entry->voided_at->format('d/m/Y H:i') }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Razon de anulacion</dt>
                    <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $entry->void_reason }}</dd>
                </div>
            @endif
        </div>
    </div>

    {{-- Lines Table --}}
    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Lineas del asiento</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Codigo</th>
                        <th class="px-4 py-3 font-medium">Cuenta</th>
                        <th class="px-4 py-3 font-medium text-right">Debito</th>
                        <th class="px-4 py-3 font-medium text-right">Credito</th>
                        <th class="px-4 py-3 font-medium">Descripcion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($entry->lines as $line)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-mono text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ $line->account->code }}
                            </td>
                            <td class="px-4 py-3 text-slate-900 dark:text-white">
                                {{ $line->account->name }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium {{ $line->debit > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600' }}">
                                ${{ number_format($line->debit, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium {{ $line->credit > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600' }}">
                                ${{ number_format($line->credit, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                {{ $line->description ?? '-' }}
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
                            ${{ number_format($entry->total_debit, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-bold text-slate-900 dark:text-white">
                            ${{ number_format($entry->total_credit, 2) }}
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Void Modal --}}
    @if($showVoidModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4" wire:click.self="closeVoidModal">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Anular asiento</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Esta accion no se puede deshacer.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label">Razon de anulacion (minimo 10 caracteres)</label>
                    <textarea wire:model.live="voidReason" rows="3" class="input w-full" placeholder="Explique el motivo de la anulacion..."></textarea>
                    @error('voidReason') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" wire:click="closeVoidModal" class="btn-ghost">Cancelar</button>
                    <button type="button" wire:click="voidEntry"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        @if(strlen($voidReason) < 10) disabled @endif>
                        Confirmar anulacion
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
