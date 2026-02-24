<div class="space-y-6">
    @if(!$activeSessionId)
        {{-- No active session - show open session UI --}}
        <div class="flex flex-col items-center justify-center py-12">
            <div class="text-center space-y-4">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-900/20">
                    <svg class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Punto de Venta</h2>
                <p class="text-slate-500 dark:text-slate-400">Abre una sesion de caja para comenzar a vender</p>
                <button wire:click="$set('showOpenSessionModal', true)" class="btn-primary btn-lg">
                    Abrir Caja
                </button>
                <div class="mt-4">
                    <a href="{{ route('panel.pos.history') }}" class="text-sm text-primary-500 hover:underline">
                        Ver historial de sesiones
                    </a>
                </div>
            </div>
        </div>

        {{-- Open Session Modal --}}
        @if($showOpenSessionModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('showOpenSessionModal', false)">
                <div class="card w-full max-w-md p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Abrir Caja</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="label">Empresa</label>
                            <select wire:model.live="companyId" class="input w-full">
                                <option value="">Seleccionar...</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->commercial_name ?: $company->business_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Sucursal</label>
                            <select wire:model.live="branchId" class="input w-full">
                                <option value="">Seleccionar...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Punto de emision</label>
                            <select wire:model="emissionPointId" class="input w-full">
                                <option value="">Seleccionar...</option>
                                @foreach($emissionPoints as $ep)
                                    <option value="{{ $ep->id }}">{{ $ep->code }} - {{ $ep->description ?? 'POS' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Monto inicial en caja</label>
                            <input type="number" step="0.01" wire:model="openingAmount" class="input w-full" placeholder="0.00" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showOpenSessionModal', false)" class="btn-secondary">Cancelar</button>
                        <button wire:click="openSession" class="btn-primary">Abrir caja</button>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- Active session - POS interface --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left: Product search + cart --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Session info bar --}}
                <div class="card p-3 flex items-center justify-between bg-primary-50 dark:bg-primary-900/20">
                    <div class="flex items-center gap-3 text-sm">
                        <span class="inline-flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="font-medium">Caja abierta</span>
                        @if($session)
                            <span class="text-slate-500">{{ $session->branch->name ?? '' }} - {{ $session->emissionPoint->code ?? '' }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('panel.pos.history') }}" class="btn-ghost text-xs">Historial</a>
                        <button wire:click="$set('showCloseSessionModal', true)" class="btn-secondary text-xs">Cerrar caja</button>
                    </div>
                </div>

                {{-- Product search --}}
                <div class="card p-4">
                    <input wire:model.live.debounce.200ms="productSearch" type="search"
                        placeholder="Buscar producto por nombre, codigo o barcode..."
                        class="input w-full text-lg" autofocus />

                    @if($searchResults->count() > 0)
                        <div class="mt-2 divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($searchResults as $product)
                                <button wire:click="addToCart({{ $product->id }})"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800 rounded">
                                    <div>
                                        <span class="font-medium">{{ $product->name }}</span>
                                        <span class="ml-2 text-xs text-slate-500">{{ $product->main_code }}</span>
                                    </div>
                                    <span class="font-semibold tabular-nums">${{ number_format($product->unit_price, 2) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Cart --}}
                <div class="card overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Carrito ({{ count($cart) }} items)</h3>
                    </div>
                    @if(count($cart) > 0)
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium">Producto</th>
                                    <th class="px-4 py-2 text-center font-medium w-24">Cant.</th>
                                    <th class="px-4 py-2 text-right font-medium">Precio</th>
                                    <th class="px-4 py-2 text-right font-medium">Total</th>
                                    <th class="px-4 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($cart as $index => $item)
                                    <tr>
                                        <td class="px-4 py-2">{{ $item['description'] }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})" class="btn-icon-xs">-</button>
                                                <span class="w-8 text-center tabular-nums">{{ $item['quantity'] }}</span>
                                                <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})" class="btn-icon-xs">+</button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format($item['unit_price'], 2) }}</td>
                                        <td class="px-4 py-2 text-right font-semibold tabular-nums">${{ number_format($item['total'], 2) }}</td>
                                        <td class="px-4 py-2">
                                            <button wire:click="removeFromCart({{ $index }})" class="text-red-400 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="px-4 py-12 text-center text-slate-400">
                            Busca y agrega productos al carrito
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: Payment panel --}}
            <div class="space-y-4">
                <div class="card p-6 space-y-4 sticky top-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Resumen</h3>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="tabular-nums">${{ number_format($this->cartSubtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">IVA</span>
                            <span class="tabular-nums">${{ number_format($this->cartTax, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t pt-2 text-lg font-bold">
                            <span>Total</span>
                            <span class="tabular-nums text-primary-600">${{ number_format($this->cartTotal, 2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="label">Forma de pago</label>
                            <select wire:model="paymentMethod" class="input w-full">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="transfer">Transferencia</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        @if($paymentMethod === 'cash')
                            <div>
                                <label class="label">Monto recibido</label>
                                <input type="number" step="0.01" wire:model.live="amountReceived" class="input w-full text-lg" />
                            </div>
                            @if($this->changeAmount > 0)
                                <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/20">
                                    <p class="text-sm text-green-600">Cambio</p>
                                    <p class="text-2xl font-bold text-green-700 tabular-nums">${{ number_format($this->changeAmount, 2) }}</p>
                                </div>
                            @endif
                        @endif

                        <div>
                            <label class="label">Cliente (opcional)</label>
                            <select wire:model="customerId" class="input w-full">
                                <option value="">Consumidor Final</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->identification }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button wire:click="processTransaction"
                        @if(count($cart) === 0) disabled @endif
                        class="btn-primary w-full py-4 text-lg font-bold {{ count($cart) === 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
                        Cobrar ${{ number_format($this->cartTotal, 2) }}
                    </button>
                </div>

                {{-- Session stats --}}
                @if($session)
                    <div class="card p-4 space-y-2 text-sm">
                        <h4 class="font-semibold text-slate-700 dark:text-slate-300">Sesion actual</h4>
                        <div class="flex justify-between"><span class="text-slate-500">Transacciones</span><span>{{ $session->total_transactions }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Ventas</span><span class="font-semibold tabular-nums">${{ number_format($session->total_sales, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Efectivo</span><span class="tabular-nums">${{ number_format($session->total_cash, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Tarjeta</span><span class="tabular-nums">${{ number_format($session->total_card, 2) }}</span></div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Close Session Modal --}}
        @if($showCloseSessionModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('showCloseSessionModal', false)">
                <div class="card w-full max-w-md p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Cerrar Caja</h3>
                    @if($session)
                        <div class="space-y-2 text-sm bg-slate-50 dark:bg-slate-800 rounded-lg p-3">
                            <div class="flex justify-between"><span>Total ventas</span><span class="font-semibold">${{ number_format($session->total_sales, 2) }}</span></div>
                            <div class="flex justify-between"><span>Efectivo esperado</span><span>${{ number_format($session->opening_amount + $session->total_cash, 2) }}</span></div>
                        </div>
                    @endif
                    <div>
                        <label class="label">Monto de cierre en caja</label>
                        <input type="number" step="0.01" wire:model="closingAmount" class="input w-full" placeholder="0.00" />
                    </div>
                    <div>
                        <label class="label">Notas de cierre</label>
                        <textarea wire:model="closingNotes" class="input w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showCloseSessionModal', false)" class="btn-secondary">Cancelar</button>
                        <button wire:click="closeSession" class="btn-primary">Cerrar caja</button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
