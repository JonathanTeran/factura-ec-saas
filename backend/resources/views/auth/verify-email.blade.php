<x-layouts.auth title="Verificar Email" subtitle="Verifica tu correo electrónico">
    @if(session('status') == 'verification-link-sent')
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 dark:bg-emerald-900/30 dark:border-emerald-800">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                    Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
                </p>
            </div>
        </div>
    @endif

    {{-- Email icon --}}
    <div class="mb-6 flex justify-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 dark:bg-teal-900/30">
            <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400 text-center">
        Gracias por registrarte. Antes de continuar, verifica tu correo electrónico haciendo clic en el enlace que te acabamos de enviar.
    </p>

    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400 text-center">
        Si no recibiste el correo, te enviaremos otro.
    </p>

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/30 hover:bg-teal-700 hover:shadow-xl hover:shadow-teal-500/40 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                Reenviar email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-layouts.auth>
