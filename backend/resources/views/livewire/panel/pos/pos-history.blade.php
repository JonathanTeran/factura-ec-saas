<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                Historial de Caja
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Registro de sesiones de punto de venta
            </p>
        </div>
        <a href="{{ route('panel.pos.index') }}" class="btn-primary">
            Ir al POS
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <select wire:model.live="status" class="input w-full sm:w-48">
                <option value="">Todos los estados</option>
                <option value="open">Abierta</option>
                <option value="closed">Cerrada</option>
            </select>
            <input type="date" wire:model.live="dateFrom" class="input w-full sm:w-48" placeholder="Desde" />
            <input type="date" wire:model.live="dateTo" class="input w-full sm:w-48" placeholder="Hasta" />
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha apertura</th>
                        <th class="px-4 py-3 font-medium">Sucursal</th>
                        <th class="px-4 py-3 font-medium">Abierta por</th>
                        <th class="px-4 py-3 font-medium">Transacciones</th>
                        <th class="px-4 py-3 font-medium">Total ventas</th>
                        <th class="px-4 py-3 font-medium">Diferencia</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium">Cierre</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 tabular-nums">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $session->branch->name ?? '-' }} / {{ $session->emissionPoint->code ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $session->openedByUser->name ?? '-' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $session->transactions_count }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums">${{ number_format($session->total_sales, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                @if($session->difference !== null)
                                    <span class="{{ $session->difference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format($session->difference, 2) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-{{ $session->status->color() }}">
                                    {{ $session->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-slate-500">
                                {{ $session->closed_at ? $session->closed_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="viewSession({{ $session->id }})"
                                    class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-xs font-medium">
                                    Ver detalle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                No hay sesiones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- SESSION DETAIL MODAL --}}
    {{-- ============================================================ --}}
    @if($showSessionDetail && $selectedSession)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 pt-10 pb-10"
             wire:click.self="closeSessionDetail">
            <div class="card w-full max-w-4xl p-0 my-4" wire:click.stop>
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Detalle de Sesion
                        </h3>
                        <p class="text-sm text-slate-500">
                            {{ $selectedSession->opened_at->format('d/m/Y H:i') }}
                            @if($selectedSession->closed_at)
                                - {{ $selectedSession->closed_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    </div>
                    <button wire:click="closeSessionDetail" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Session Summary --}}
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Sucursal</p>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm mt-0.5">{{ $selectedSession->branch->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Punto Emision</p>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm mt-0.5">{{ $selectedSession->emissionPoint->code ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Operador</p>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm mt-0.5">{{ $selectedSession->openedByUser->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Estado</p>
                            <p class="mt-0.5">
                                <span class="badge badge-{{ $selectedSession->status->color() }}">
                                    {{ $selectedSession->status->label() }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div>
                            <p class="text-xs text-slate-500">Monto apertura</p>
                            <p class="font-semibold tabular-nums">${{ number_format($selectedSession->opening_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Total ventas</p>
                            <p class="font-semibold tabular-nums text-green-600">${{ number_format($selectedSession->total_sales, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Efectivo</p>
                            <p class="font-semibold tabular-nums">${{ number_format($selectedSession->total_cash, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Tarjeta</p>
                            <p class="font-semibold tabular-nums">${{ number_format($selectedSession->total_card, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Transferencia</p>
                            <p class="font-semibold tabular-nums">${{ number_format($selectedSession->total_transfer, 2) }}</p>
                        </div>
                    </div>

                    @if($selectedSession->closed_at)
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <div>
                                <p class="text-xs text-slate-500">Monto cierre</p>
                                <p class="font-semibold tabular-nums">${{ number_format($selectedSession->closing_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Esperado</p>
                                <p class="font-semibold tabular-nums">${{ number_format($selectedSession->expected_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Diferencia</p>
                                <p class="font-semibold tabular-nums {{ ($selectedSession->difference ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($selectedSession->difference, 2) }}
                                </p>
                            </div>
                        </div>
                        @if($selectedSession->closing_notes)
                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <p class="text-xs text-slate-500">Notas de cierre</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300 mt-0.5">{{ $selectedSession->closing_notes }}</p>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Transactions Table --}}
                <div class="px-6 py-4">
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                        Transacciones ({{ $selectedSession->transactions->count() }})
                    </h4>

                    @if($selectedSession->transactions->count() > 0)
                        <div class="overflow-x-auto -mx-6">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-6 py-2 text-left font-medium text-xs text-slate-500">#</th>
                                        <th class="px-3 py-2 text-left font-medium text-xs text-slate-500">Hora</th>
                                        <th class="px-3 py-2 text-left font-medium text-xs text-slate-500">Cliente</th>
                                        <th class="px-3 py-2 text-left font-medium text-xs text-slate-500">Metodo</th>
                                        <th class="px-3 py-2 text-right font-medium text-xs text-slate-500">Total</th>
                                        <th class="px-3 py-2 text-center font-medium text-xs text-slate-500">Estado</th>
                                        <th class="px-6 py-2 text-right font-medium text-xs text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($selectedSession->transactions as $tx)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                            <td class="px-6 py-2.5 font-mono text-xs">{{ $tx->transaction_number }}</td>
                                            <td class="px-3 py-2.5 tabular-nums text-slate-500">{{ $tx->created_at->format('H:i') }}</td>
                                            <td class="px-3 py-2.5">{{ $tx->customer->name ?? 'Consumidor Final' }}</td>
                                            <td class="px-3 py-2.5">
                                                @switch($tx->payment_method)
                                                    @case('cash') Efectivo @break
                                                    @case('card') Tarjeta @break
                                                    @case('transfer') Transferencia @break
                                                    @default Otro
                                                @endswitch
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-semibold tabular-nums">${{ number_format($tx->total, 2) }}</td>
                                            <td class="px-3 py-2.5 text-center">
                                                @if($tx->isVoided())
                                                    <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/20 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400 ring-1 ring-red-200 dark:ring-red-800">
                                                        Anulada
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/20 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-green-200 dark:ring-green-800">
                                                        Completada
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-2.5 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button wire:click="viewTransaction({{ $tx->id }})"
                                                        class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-xs font-medium">
                                                        Ver
                                                    </button>
                                                    @if($tx->isCompleted())
                                                        <button wire:click="confirmVoid({{ $tx->id }})"
                                                            class="text-red-500 hover:text-red-700 dark:text-red-400 text-xs font-medium">
                                                            Anular
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-slate-400 py-6">No hay transacciones en esta sesion.</p>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="flex justify-end border-t border-slate-200 dark:border-slate-700 px-6 py-4">
                    <button wire:click="closeSessionDetail" class="btn-secondary">Cerrar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TRANSACTION DETAIL MODAL --}}
    {{-- ============================================================ --}}
    @if($showTransactionDetail && $selectedTransaction)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
             wire:click.self="closeTransactionDetail">
            <div class="card w-full max-w-lg p-0" wire:click.stop>
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Transaccion {{ $selectedTransaction->transaction_number }}
                        </h3>
                        <p class="text-sm text-slate-500">
                            {{ $selectedTransaction->created_at->format('d/m/Y H:i:s') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($selectedTransaction->isVoided())
                            <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/20 px-2.5 py-1 text-xs font-semibold text-red-700 dark:text-red-400 ring-1 ring-red-200 dark:ring-red-800">
                                Anulada
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/20 px-2.5 py-1 text-xs font-semibold text-green-700 dark:text-green-400 ring-1 ring-green-200 dark:ring-green-800">
                                Completada
                            </span>
                        @endif
                        <button wire:click="closeTransactionDetail" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Transaction Info --}}
                <div class="px-6 py-4 space-y-4">
                    {{-- Customer & Payment --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Cliente</p>
                            <p class="font-medium mt-0.5">{{ $selectedTransaction->customer->name ?? 'Consumidor Final' }}</p>
                            @if($selectedTransaction->customer)
                                <p class="text-xs text-slate-400">{{ $selectedTransaction->customer->identification }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Forma de pago</p>
                            <p class="font-medium mt-0.5">
                                @switch($selectedTransaction->payment_method)
                                    @case('cash') Efectivo @break
                                    @case('card') Tarjeta @break
                                    @case('transfer') Transferencia @break
                                    @default Otro
                                @endswitch
                            </p>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Items</p>
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-xs text-slate-500">Descripcion</th>
                                        <th class="px-3 py-2 text-center font-medium text-xs text-slate-500">Cant.</th>
                                        <th class="px-3 py-2 text-right font-medium text-xs text-slate-500">P. Unit.</th>
                                        <th class="px-3 py-2 text-right font-medium text-xs text-slate-500">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($selectedTransaction->items as $item)
                                        <tr>
                                            <td class="px-3 py-2">{{ $item->description }}</td>
                                            <td class="px-3 py-2 text-center tabular-nums">{{ intval($item->quantity) }}</td>
                                            <td class="px-3 py-2 text-right tabular-nums">${{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-3 py-2 text-right font-semibold tabular-nums">${{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-4 space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="tabular-nums">${{ number_format($selectedTransaction->subtotal, 2) }}</span>
                        </div>
                        @if($selectedTransaction->discount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Descuento</span>
                                <span class="tabular-nums text-red-600">-${{ number_format($selectedTransaction->discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">IVA</span>
                            <span class="tabular-nums">${{ number_format($selectedTransaction->tax, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold pt-1.5 border-t border-slate-200 dark:border-slate-700">
                            <span>Total</span>
                            <span class="tabular-nums text-primary-600">${{ number_format($selectedTransaction->total, 2) }}</span>
                        </div>
                        @if($selectedTransaction->payment_method === 'cash')
                            <div class="flex justify-between text-sm pt-1.5 border-t border-slate-200 dark:border-slate-700">
                                <span class="text-slate-500">Recibido</span>
                                <span class="tabular-nums">${{ number_format($selectedTransaction->amount_received, 2) }}</span>
                            </div>
                            @if($selectedTransaction->change_amount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Cambio</span>
                                    <span class="tabular-nums text-green-600">${{ number_format($selectedTransaction->change_amount, 2) }}</span>
                                </div>
                            @endif
                        @endif
                    </div>

                    @if($selectedTransaction->notes)
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Notas</p>
                            <p class="text-sm text-slate-700 dark:text-slate-300 mt-0.5">{{ $selectedTransaction->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700 px-6 py-4">
                    <div>
                        @if($selectedTransaction->isCompleted())
                            <button wire:click="confirmVoid({{ $selectedTransaction->id }})"
                                class="text-red-500 hover:text-red-700 text-sm font-medium">
                                Anular transaccion
                            </button>
                        @endif
                    </div>
                    <button wire:click="closeTransactionDetail" class="btn-secondary">Cerrar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- VOID CONFIRMATION MODAL --}}
    {{-- ============================================================ --}}
    @if($showVoidConfirm)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50"
             wire:click.self="cancelVoid">
            <div class="card w-full max-w-sm p-6 space-y-4 text-center" wire:click.stop>
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Anular transaccion</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Esta accion es irreversible. Se revertira el inventario y los totales de la sesion.
                    </p>
                </div>
                <div class="flex gap-3 justify-center">
                    <button wire:click="cancelVoid" class="btn-secondary">Cancelar</button>
                    <button wire:click="voidTransaction"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                        Confirmar anulacion
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
