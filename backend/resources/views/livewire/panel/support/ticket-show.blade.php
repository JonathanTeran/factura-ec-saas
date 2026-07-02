<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('panel.support.index') }}" class="text-slate-500 hover:text-slate-700 dark:text-slate-400">&larr;</a>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                    #{{ $ticket->id }} — {{ $ticket->subject }}
                </h1>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="badge badge-{{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
                <span class="badge badge-{{ $ticket->priority->color() }}">{{ $ticket->priority->label() }}</span>
                <span class="badge badge-{{ $ticket->category->color() }}">{{ $ticket->category->label() }}</span>
                <span class="text-xs text-slate-500">Creado {{ $ticket->created_at->diffForHumans() }}</span>
            </div>
        </div>

        @if ($ticket->isOpen())
            <button wire:click="resolve"
                wire:confirm="¿Marcar este ticket como resuelto?"
                class="btn-secondary shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Marcar como resuelto
            </button>
        @endif
    </div>

    {{-- Messages thread --}}
    <div class="space-y-4">
        @foreach ($ticket->messages as $msg)
            <div class="flex {{ $msg->is_admin_reply ? 'justify-start' : 'justify-end' }}">
                <div class="max-w-2xl {{ $msg->is_admin_reply
                    ? 'rounded-lg rounded-tl-none border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800'
                    : 'rounded-lg rounded-tr-none bg-primary-600 text-white' }} p-4 shadow-sm">

                    <div class="mb-1 flex items-center gap-2 text-xs {{ $msg->is_admin_reply ? 'text-slate-500' : 'text-primary-100' }}">
                        @if ($msg->is_admin_reply)
                            <span class="font-semibold text-primary-600 dark:text-primary-400">Soporte</span>
                        @else
                            <span class="font-semibold">{{ $msg->user->name }}</span>
                        @endif
                        <span>·</span>
                        <span>{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ $msg->message }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Reply form --}}
    @if ($ticket->isOpen())
        <div class="card p-6">
            <h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Responder</h3>
            <form wire:submit="sendMessage" class="space-y-3">
                <textarea wire:model="newMessage" class="input w-full" rows="4"
                    placeholder="Escribe tu respuesta..."></textarea>
                @error('newMessage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">Enviar respuesta</button>
                </div>
            </form>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800">
            Este ticket está {{ $ticket->status->label() }}. Si necesitas más ayuda, abre un nuevo ticket.
        </div>
    @endif
</div>
