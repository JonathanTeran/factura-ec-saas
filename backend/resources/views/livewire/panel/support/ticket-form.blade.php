<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Nuevo Ticket de Soporte</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Describe tu problema y te responderemos lo antes posible</p>
        </div>
        <a href="{{ route('panel.support.index') }}" class="btn-secondary">Volver</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="card p-6 space-y-4">
            <div>
                <label class="label">Asunto</label>
                <input type="text" wire:model="subject" class="input w-full" placeholder="Describe brevemente tu problema" />
                @error('subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Categoría</label>
                    <select wire:model="category" class="input w-full">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Prioridad</label>
                    <select wire:model="priority" class="input w-full">
                        @foreach($priorities as $p)
                            <option value="{{ $p->value }}">{{ $p->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="label">Descripción del problema</label>
                <textarea wire:model="message" class="input w-full" rows="6"
                    placeholder="Describe detalladamente el problema, incluye pasos para reproducirlo, capturas de pantalla, etc."></textarea>
                @error('message') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('panel.support.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Enviar ticket</button>
        </div>
    </form>
</div>
