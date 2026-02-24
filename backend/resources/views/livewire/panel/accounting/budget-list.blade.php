<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Presupuestos
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Gestion y control de presupuestos anuales
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('panel.accounting.budget-execution') }}" class="btn-secondary" wire:navigate>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                Ver ejecucion
            </a>
            <a href="{{ route('panel.accounting.budgets.create') }}" class="btn-primary" wire:navigate>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo presupuesto
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre..."
                    class="input w-full" />
            </div>
            <select wire:model.live="status" class="input w-full sm:w-48">
                <option value="">Todos los estados</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="year" class="input w-full sm:w-36">
                <option value="">Todos los anos</option>
                @foreach($availableYears as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            @if($search || $status || $year)
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
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('year')">
                            Ano
                            @if($sortField === 'year')
                                <svg class="ml-1 inline h-3 w-3 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 font-medium cursor-pointer" wire:click="sortBy('name')">Nombre</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium cursor-pointer" wire:click="sortBy('total_amount')">Monto Total</th>
                        <th class="px-4 py-3 text-right font-medium">Ejecucion</th>
                        <th class="px-4 py-3 font-medium">Creado por</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($budgets as $budget)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-semibold tabular-nums">{{ $budget->year }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $budget->name }}</span>
                                @if($budget->notes)
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]">{{ $budget->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $budget->status->color() }}">
                                    {{ $budget->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">${{ number_format($budget->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @php $execution = $budget->getExecutionPercentage(); @endphp
                                <div class="flex items-center justify-end gap-2">
                                    <div class="h-2 w-20 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                        <div class="h-full rounded-full transition-all
                                            {{ $execution > 100 ? 'bg-red-500' : ($execution > 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                            style="width: {{ min($execution, 100) }}%">
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium tabular-nums {{ $execution > 100 ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-400' }}">
                                        {{ number_format($execution, 1) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                {{ $budget->createdByUser?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('panel.accounting.budgets.edit', $budget) }}" class="btn-icon-sm" title="Editar" wire:navigate>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>
                                    @if($budget->status->value === 'draft')
                                        <button wire:click="approveBudget({{ $budget->id }})"
                                                wire:confirm="Aprobar este presupuesto?"
                                                class="btn-icon-sm text-blue-500 hover:text-blue-700" title="Aprobar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif
                                    @if($budget->status->value === 'approved')
                                        <button wire:click="activateBudget({{ $budget->id }})"
                                                wire:confirm="Activar este presupuesto?"
                                                class="btn-icon-sm text-emerald-500 hover:text-emerald-700" title="Activar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                                            </svg>
                                        </button>
                                    @endif
                                    @if(in_array($budget->status->value, ['active', 'approved']))
                                        <button wire:click="closeBudget({{ $budget->id }})"
                                                wire:confirm="Cerrar este presupuesto? Esta accion no se puede deshacer."
                                                class="btn-icon-sm text-red-500 hover:text-red-700" title="Cerrar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                        </button>
                                    @endif
                                    <a href="{{ route('panel.accounting.budget-execution') }}?budget={{ $budget->id }}"
                                       class="btn-icon-sm" title="Ver ejecucion" wire:navigate>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                No hay presupuestos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($budgets->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $budgets->links() }}
            </div>
        @endif
    </div>
</div>
