<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Gastos Personales Deducibles
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Registra tus gastos personales deducibles para la declaración anual de IR
            </p>
        </div>
        <a href="{{ route('panel.personal-expenses.create') }}" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo gasto
        </a>
    </div>

    {{-- Year selector + Stats --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        {{-- Stats cards --}}
        <div class="stat-card">
            <div class="stat-card-glow bg-primary-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-primary-500 to-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">${{ number_format($stats['total'], 2) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total {{ $fiscalYear }}</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-glow bg-emerald-500"></div>
            <div class="flex items-center gap-4">
                <div class="stat-icon bg-gradient-to-br from-emerald-500 to-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                </div>
                <div>
                    <p class="stat-value tabular-nums text-2xl">{{ $stats['count'] }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Comprobantes</p>
                </div>
            </div>
        </div>

        {{-- Top categories --}}
        <div class="lg:col-span-2 card p-4">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">Top categorías {{ $fiscalYear }}</p>
            <div class="space-y-1">
                @forelse(array_slice($stats['by_category'], 0, 3, true) as $key => $cat)
                    <div class="flex items-center justify-between text-sm">
                        <span class="badge badge-{{ $cat['color'] }}">{{ $cat['label'] }}</span>
                        <span class="font-semibold tabular-nums">${{ number_format($cat['amount'], 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Sin gastos registrados</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <select wire:model.live="fiscalYear" class="input w-full sm:w-32">
                @foreach($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Buscar descripción, emisor..."
                    class="input w-full" />
            </div>
            <select wire:model.live="category" class="input w-full sm:w-48">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                @endforeach
            </select>
            @if($search || $category)
                <button wire:click="clearFilters" class="btn-ghost text-sm">Limpiar</button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('issue_date')">Fecha</th>
                        <th class="px-4 py-3 font-medium">Descripción</th>
                        <th class="px-4 py-3 font-medium">Emisor</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('amount')">Monto</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($expenses as $expense)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 tabular-nums whitespace-nowrap">{{ $expense->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $expense->description }}</div>
                                @if($expense->document_number)
                                    <div class="text-xs text-slate-500 font-mono">{{ $expense->document_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($expense->issuer_name)
                                    <div>{{ $expense->issuer_name }}</div>
                                    @if($expense->issuer_ruc)
                                        <div class="text-xs text-slate-500 font-mono">{{ $expense->issuer_ruc }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $expense->category->color() }}">
                                    {{ $expense->category->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums">${{ number_format($expense->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('panel.personal-expenses.edit', $expense) }}" class="btn-icon-sm" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </a>
                                    <button wire:click="delete({{ $expense->id }})"
                                        wire:confirm="¿Eliminar este gasto?"
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
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                No hay gastos personales registrados para {{ $fiscalYear }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
</div>
