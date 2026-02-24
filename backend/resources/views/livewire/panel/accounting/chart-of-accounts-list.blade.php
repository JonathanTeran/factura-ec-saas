<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Plan de Cuentas
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Catalogo de cuentas contables de la empresa
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('panel.accounting.dashboard') }}" class="btn-secondary">
                Contabilidad
            </a>
            <a href="{{ route('panel.accounting.chart-of-accounts.create') }}" class="btn-primary">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva Cuenta
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por codigo o nombre..."
                    class="input w-full" />
            </div>
            <select wire:model.live="accountType" class="input w-full sm:w-48">
                <option value="">Todos los tipos</option>
                @foreach($accountTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            @if($companies->count() > 1)
                <select wire:model.live="companyId" class="input w-full sm:w-48">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->business_name }}</option>
                    @endforeach
                </select>
            @endif
            @if($search || $accountType)
                <button wire:click="clearFilters" class="btn-ghost text-sm">Limpiar</button>
            @endif
        </div>
    </div>

    {{-- Accounts Tree / Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Codigo</th>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">Naturaleza</th>
                        <th class="px-4 py-3 font-medium text-center">Movimiento</th>
                        <th class="px-4 py-3 font-medium text-center">Estado</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @if($search || $accountType)
                        {{-- Flat list when searching --}}
                        @forelse($accounts as $account)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-mono text-xs font-medium">{{ $account->code }}</td>
                                <td class="px-4 py-3">
                                    <span style="padding-left: {{ ($account->level - 1) * 1.5 }}rem">
                                        {{ $account->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge badge-{{ $account->account_type->color() }}">
                                        {{ $account->account_type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ $account->account_nature === 'debit' ? 'Deudora' : 'Acreedora' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($account->allows_movement)
                                        <svg class="mx-auto h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="mx-auto h-4 w-4 text-slate-300 dark:text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleActive({{ $account->id }})"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $account->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $account->is_active ? 'Activa' : 'Inactiva' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('panel.accounting.chart-of-accounts.edit', $account->id) }}" class="btn-icon-sm" title="Editar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('panel.accounting.chart-of-accounts.create', ['parentId' => $account->id]) }}" class="btn-icon-sm" title="Agregar subcuenta">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </a>
                                        <button wire:click="deleteAccount({{ $account->id }})"
                                            wire:confirm="Esta seguro de eliminar esta cuenta?"
                                            class="btn-icon-sm text-red-500 hover:text-red-700" title="Eliminar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                    No se encontraron cuentas.
                                </td>
                            </tr>
                        @endforelse
                    @else
                        {{-- Tree view --}}
                        @forelse($accounts as $account)
                            @include('livewire.panel.accounting.partials.account-tree-row', ['account' => $account, 'level' => 0])
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                        </svg>
                                        <p>No hay cuentas registradas.</p>
                                        <a href="{{ route('panel.accounting.setup') }}" class="text-sm text-primary-600 hover:text-primary-700">
                                            Ejecutar asistente de configuracion
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
