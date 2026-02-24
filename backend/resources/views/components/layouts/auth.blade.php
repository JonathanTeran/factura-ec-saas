@props([
    'title' => 'Autenticación',
    'subtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ config('app.name', 'AmePhia Facturacion') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        .auth-gradient {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
        }
        .pattern-dots {
            background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="h-full font-sans antialiased bg-slate-50 dark:bg-slate-950">
    <div class="flex min-h-full">
        {{-- Left panel - Branding --}}
        <div class="hidden lg:flex lg:w-1/2 xl:w-[55%] relative auth-gradient">
            {{-- Pattern overlay --}}
            <div class="absolute inset-0 pattern-dots"></div>

            {{-- Content --}}
            <div class="relative flex flex-col justify-between p-12 text-white">
                {{-- Logo --}}
                <div>
                    <a href="/" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo_dark_bg_v2.png') }}" alt="AmePhia Facturacion" class="h-10 object-contain">
                    </a>
                </div>

                {{-- Main content --}}
                <div class="max-w-lg">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                        Facturación electrónica simple y poderosa
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-teal-100">
                        Emite facturas, notas de crédito y más documentos electrónicos autorizados por el SRI. Todo desde una plataforma moderna y fácil de usar.
                    </p>

                    {{-- Features --}}
                    <div class="mt-10 grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium">Autorizado por SRI</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium">Firma electrónica</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium">Envío automático</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium">App móvil</span>
                        </div>
                    </div>
                </div>

                {{-- Testimonial --}}
                <div class="max-w-lg">
                    <blockquote class="text-lg italic text-teal-100">
                        "AmePhia ha simplificado completamente nuestra facturacion. Ahora emitimos documentos en segundos."
                    </blockquote>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-white/20"></div>
                        <div>
                            <p class="font-medium">María González</p>
                            <p class="text-sm text-teal-200">CEO, Comercial Express</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Decorative elements --}}
            <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-teal-500/20 blur-3xl"></div>
        </div>

        {{-- Right panel - Form --}}
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:px-12 xl:px-24 bg-white dark:bg-slate-900">
            <div class="mx-auto w-full max-w-sm">
                {{-- Mobile logo --}}
                <div class="mb-8 lg:hidden">
                    <a href="/" class="flex items-center gap-3">
                        <img src="{{ asset('images/app_icon_transparent.png') }}" alt="AmePhia" class="h-10 w-10 rounded-xl object-contain">
                        <img src="{{ asset('images/amelogo_v3_optimized.webp') }}" alt="AmePhia Facturacion" class="h-8 object-contain">
                    </a>
                </div>

                {{-- Header --}}
                <div class="mb-8">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ $title }}
                    </h2>
                    @if($subtitle)
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                    @endif
                </div>

                {{-- Dark mode toggle --}}
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                        class="absolute top-4 right-4 p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-slate-300 dark:hover:bg-slate-800 transition-all">
                    <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                    <svg x-show="darkMode" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </button>

                {{-- Form content --}}
                {{ $slot }}

                {{-- Footer --}}
                @if(isset($footer))
                    <div class="mt-8 text-center">
                        {{ $footer }}
                    </div>
                @endif
            </div>

            {{-- Bottom links --}}
            <div class="mt-auto pt-8">
                <div class="mx-auto max-w-sm flex items-center justify-center gap-6 text-xs text-slate-400 dark:text-slate-500">
                    <a href="#" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Términos</a>
                    <a href="#" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Privacidad</a>
                    <a href="#" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Ayuda</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
