<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ $expenseId ? 'Editar Gasto Personal' : 'Nuevo Gasto Personal Deducible' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Registra comprobantes de gastos personales deducibles para tu declaración de IR
            </p>
        </div>
        <a href="{{ route('panel.personal-expenses.index') }}" class="btn-secondary">Volver</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="card p-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="label">Año fiscal</label>
                    <select wire:model="fiscalYear" class="input w-full">
                        @foreach($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Categoría</label>
                    <select wire:model="category" class="input w-full">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Fecha del comprobante</label>
                    <input type="date" wire:model="issueDate" class="input w-full" />
                    @error('issueDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label">Descripción</label>
                    <input type="text" wire:model="description" class="input w-full" placeholder="Ej: Consulta médica, mensualidad colegio, arriendo..." />
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label">Monto ($)</label>
                    <input type="number" step="0.01" wire:model="amount" class="input w-full" min="0.01" />
                    @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="card p-6 space-y-4">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Datos del comprobante (opcional)</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="label">RUC del emisor</label>
                    <input type="text" wire:model="issuerRuc" class="input w-full font-mono" maxlength="13" placeholder="1234567890001" />
                </div>

                <div>
                    <label class="label">Razón social del emisor</label>
                    <input type="text" wire:model="issuerName" class="input w-full" />
                </div>

                <div>
                    <label class="label">No. del comprobante</label>
                    <input type="text" wire:model="documentNumber" class="input w-full" placeholder="001-001-000000001" />
                </div>
            </div>

            <div>
                <label class="label">Notas adicionales</label>
                <textarea wire:model="notes" class="input w-full" rows="2" placeholder="Observaciones..."></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('panel.personal-expenses.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $expenseId ? 'Actualizar gasto' : 'Registrar gasto' }}
            </button>
        </div>
    </form>
</div>
