<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', mobileMenu: false }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Facturacion electronica autorizada por el SRI. Emite facturas, notas de credito, retenciones y guias de remision en segundos. Cumple con la normativa ecuatoriana sin complicaciones.">
    <meta name="keywords"
        content="facturacion electronica ecuador, SRI, facturas electronicas, notas de credito, retenciones, guias de remision, firma electronica">

    <title>{{ config('app.name', 'AmePhia Facturacion') }} — Facturacion Electronica Ecuador</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&family=space-grotesk:500,600,700&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        .font-display {
            font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
        }

        /* Subtle noise texture */
        .noise::after {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.025;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        .dark .noise::after {
            opacity: 0.04;
        }

        /* Number counter animation */
        @keyframes count-up {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .count-in {
            animation: count-up 0.6s ease-out both;
        }

        /* Smooth reveal */
        [x-intersect] {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        [x-intersect].is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body
    class="font-sans antialiased bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 selection:bg-teal-500/20 selection:text-teal-900 dark:selection:text-teal-100">

    {{-- ============================================================ --}}
    {{-- NAVIGATION --}}
    {{-- ============================================================ --}}
    <header class="fixed top-0 left-0 right-0 z-50">
        <nav class="bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="flex h-[72px] items-center justify-between">
                    <a href="/" class="relative z-10 flex items-center gap-2.5 shrink-0">
                        <img src="{{ asset('images/app_icon_transparent.png') }}" alt=""
                            class="h-8 w-8 object-contain">
                        <span
                            class="font-display font-bold text-lg tracking-tight text-slate-900 dark:text-white">AmePhia<span
                                class="text-teal-600 dark:text-teal-400">.</span></span>
                    </a>

                    <div class="hidden lg:flex lg:items-center lg:gap-1">
                        <a href="#funcionalidades"
                            class="px-4 py-2 text-[13px] font-medium text-slate-500 dark:text-slate-400 rounded-lg transition-colors hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60">Funcionalidades</a>
                        <a href="#planes"
                            class="px-4 py-2 text-[13px] font-medium text-slate-500 dark:text-slate-400 rounded-lg transition-colors hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60">Planes</a>
                        <a href="#testimonios"
                            class="px-4 py-2 text-[13px] font-medium text-slate-500 dark:text-slate-400 rounded-lg transition-colors hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60">Testimonios</a>
                        <a href="#faq"
                            class="px-4 py-2 text-[13px] font-medium text-slate-500 dark:text-slate-400 rounded-lg transition-colors hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60">Preguntas</a>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Dark mode toggle --}}
                        <button @click="darkMode = !darkMode"
                            class="relative h-9 w-9 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                            aria-label="Cambiar modo oscuro">
                            <svg x-show="!darkMode" x-transition.opacity class="h-[18px] w-[18px]" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                            <svg x-show="darkMode" x-cloak x-transition.opacity class="h-[18px] w-[18px]" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                        </button>

                        @auth
                            <a href="{{ route('panel.dashboard') }}"
                                class="hidden sm:inline-flex px-4 py-2 text-[13px] font-medium text-slate-600 dark:text-slate-300 transition-colors hover:text-slate-900 dark:hover:text-white">
                                Mi Portal
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="hidden sm:inline-flex px-4 py-2 text-[13px] font-medium text-slate-600 dark:text-slate-300 transition-colors hover:text-slate-900 dark:hover:text-white">
                                Ingresar
                            </a>
                        @endauth

                        <a href="{{ route('register') }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-teal-600 hover:bg-teal-700 px-5 py-2.5 text-[13px] font-semibold text-white transition-all shadow-sm shadow-teal-600/20 hover:shadow-md hover:shadow-teal-600/25">
                            Comenzar ahora
                        </a>

                        <button @click="mobileMenu = !mobileMenu"
                            class="lg:hidden ml-1 p-2 text-slate-500 dark:text-slate-400">
                            <svg x-show="!mobileMenu" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                            </svg>
                            <svg x-show="mobileMenu" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Mobile menu --}}
                <div x-show="mobileMenu" x-cloak x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="lg:hidden pb-5 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <div class="flex flex-col gap-1">
                        <a href="#funcionalidades" @click="mobileMenu = false"
                            class="px-3 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">Funcionalidades</a>
                        <a href="#planes" @click="mobileMenu = false"
                            class="px-3 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">Planes</a>
                        <a href="#testimonios" @click="mobileMenu = false"
                            class="px-3 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">Testimonios</a>
                        <a href="#faq" @click="mobileMenu = false"
                            class="px-3 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">Preguntas</a>
                        <hr class="my-2 border-slate-100 dark:border-slate-800">
                        @auth
                            <a href="{{ route('panel.dashboard') }}"
                                class="px-3 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">Mi
                                Portal</a>
                        @else
                            <a href="{{ route('login') }}"
                                class="px-3 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">Ingresar</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    {{-- ============================================================ --}}
    {{-- HERO --}}
    {{-- ============================================================ --}}
    <section class="relative overflow-hidden pt-[72px]">
        {{-- Background --}}
        <div
            class="absolute inset-0 bg-gradient-to-b from-slate-50 via-white to-white dark:from-slate-950 dark:via-slate-950 dark:to-slate-950">
        </div>
        <div
            class="absolute top-0 right-0 w-[800px] h-[800px] bg-teal-500/[0.04] dark:bg-teal-500/[0.03] rounded-full blur-3xl -translate-y-1/2 translate-x-1/3">
        </div>
        <div
            class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-cyan-500/[0.03] dark:bg-cyan-500/[0.02] rounded-full blur-3xl translate-y-1/3 -translate-x-1/4">
        </div>

        <div class="relative mx-auto max-w-6xl px-5 sm:px-8 pt-16 sm:pt-24 lg:pt-32 pb-20 lg:pb-28">
            <div class="lg:grid lg:grid-cols-12 lg:gap-12 items-center">
                {{-- Left: Copy --}}
                <div class="lg:col-span-6 xl:col-span-5">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-teal-50 dark:bg-teal-500/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide uppercase text-teal-700 dark:text-teal-400 ring-1 ring-teal-200/60 dark:ring-teal-500/20 mb-6">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                clip-rule="evenodd" />
                        </svg>
                        Autorizado por el SRI
                    </div>

                    <h1
                        class="font-display text-[2.5rem] sm:text-5xl lg:text-[3.25rem] xl:text-[3.5rem] font-bold leading-[1.08] tracking-tight text-slate-900 dark:text-white">
                        Facturacion
                        <span class="relative">
                            electronica
                            <svg class="absolute -bottom-1 left-0 w-full" viewBox="0 0 200 8" fill="none">
                                <path d="M1 5.5C47 2 154 2 199 5.5" stroke="#0d9488" stroke-width="2"
                                    stroke-linecap="round" opacity="0.5" />
                            </svg>
                        </span>
                        que simplifica tu negocio
                    </h1>

                    <p class="mt-5 text-base sm:text-lg text-slate-500 dark:text-slate-400 leading-relaxed max-w-lg">
                        Emite facturas, notas de credito, retenciones y guias de remision en segundos. Todo firmado
                        digitalmente y autorizado por el SRI.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-start gap-3">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-slate-900 dark:bg-white px-7 py-3.5 text-sm font-semibold text-white dark:text-slate-900 transition-all hover:bg-slate-800 dark:hover:bg-slate-100 shadow-lg shadow-slate-900/10 dark:shadow-none">
                            Comenzar ahora
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <a href="#funcionalidades"
                            class="inline-flex items-center gap-2 px-5 py-3.5 text-sm font-medium text-slate-600 dark:text-slate-300 transition-colors hover:text-slate-900 dark:hover:text-white">
                            Ver funcionalidades
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </a>
                    </div>

                    {{-- Trust indicators --}}
                    <div class="mt-10 pt-8 border-t border-slate-100 dark:border-slate-800/60">
                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">2,400+
                                </div>
                                <div class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Empresas activas</div>
                            </div>
                            <div>
                                <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">1.2M</div>
                                <div class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Documentos emitidos
                                </div>
                            </div>
                            <div>
                                <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">99.9%</div>
                                <div class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Disponibilidad</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: App mockup --}}
                <div class="mt-12 lg:mt-0 lg:col-span-6 xl:col-span-7">
                    <div class="relative">
                        {{-- Shadow/glow --}}
                        <div
                            class="absolute -inset-4 bg-gradient-to-tr from-teal-500/10 via-transparent to-cyan-500/10 dark:from-teal-500/5 dark:to-cyan-500/5 rounded-3xl blur-2xl">
                        </div>

                        {{-- Browser frame --}}
                        <div
                            class="relative rounded-2xl bg-slate-900 dark:bg-slate-800 shadow-2xl shadow-slate-900/20 dark:shadow-black/40 ring-1 ring-slate-800 dark:ring-slate-700 overflow-hidden">
                            {{-- Title bar --}}
                            <div
                                class="flex items-center gap-3 px-4 py-3 bg-slate-800/80 dark:bg-slate-750 border-b border-slate-700/50">
                                <div class="flex gap-1.5">
                                    <div class="h-2.5 w-2.5 rounded-full bg-slate-600"></div>
                                    <div class="h-2.5 w-2.5 rounded-full bg-slate-600"></div>
                                    <div class="h-2.5 w-2.5 rounded-full bg-slate-600"></div>
                                </div>
                                <div class="flex-1 flex justify-center">
                                    <div
                                        class="flex items-center gap-1.5 rounded-md bg-slate-700/50 px-3 py-1 text-[11px] text-slate-400">
                                        <svg class="h-3 w-3 text-emerald-500" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        facturacion.amephia.com/panel
                                    </div>
                                </div>
                            </div>

                            {{-- App content mockup --}}
                            <div class="p-4 sm:p-5">
                                {{-- Top bar --}}
                                <div class="flex items-center justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-7 w-7 rounded-lg bg-teal-500/20 flex items-center justify-center">
                                            <div class="h-3.5 w-3.5 rounded bg-teal-500/60"></div>
                                        </div>
                                        <div>
                                            <div class="h-2.5 w-24 rounded bg-slate-700"></div>
                                            <div class="h-2 w-16 rounded bg-slate-700/50 mt-1.5"></div>
                                        </div>
                                    </div>
                                    <div class="h-7 w-20 rounded-md bg-teal-600/30"></div>
                                </div>

                                {{-- Stats row --}}
                                <div class="grid grid-cols-4 gap-3 mb-5">
                                    <div class="rounded-lg bg-slate-800/60 p-3 ring-1 ring-slate-700/40">
                                        <div class="h-2 w-10 rounded bg-slate-600/60 mb-2"></div>
                                        <div class="h-4 w-8 rounded bg-teal-500/40"></div>
                                    </div>
                                    <div class="rounded-lg bg-slate-800/60 p-3 ring-1 ring-slate-700/40">
                                        <div class="h-2 w-12 rounded bg-slate-600/60 mb-2"></div>
                                        <div class="h-4 w-14 rounded bg-emerald-500/40"></div>
                                    </div>
                                    <div class="rounded-lg bg-slate-800/60 p-3 ring-1 ring-slate-700/40">
                                        <div class="h-2 w-9 rounded bg-slate-600/60 mb-2"></div>
                                        <div class="h-4 w-10 rounded bg-amber-500/40"></div>
                                    </div>
                                    <div class="rounded-lg bg-slate-800/60 p-3 ring-1 ring-slate-700/40">
                                        <div class="h-2 w-11 rounded bg-slate-600/60 mb-2"></div>
                                        <div class="h-4 w-12 rounded bg-cyan-500/40"></div>
                                    </div>
                                </div>

                                {{-- Main content area --}}
                                <div class="grid grid-cols-5 gap-3">
                                    <div class="col-span-3 rounded-lg bg-slate-800/40 p-3 ring-1 ring-slate-700/30">
                                        <div class="h-2.5 w-20 rounded bg-slate-600/50 mb-3"></div>
                                        <div
                                            class="h-24 rounded bg-gradient-to-r from-teal-500/10 via-teal-500/5 to-transparent">
                                        </div>
                                    </div>
                                    <div class="col-span-2 rounded-lg bg-slate-800/40 p-3 ring-1 ring-slate-700/30">
                                        <div class="h-2.5 w-16 rounded bg-slate-600/50 mb-3"></div>
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-teal-500/50"></div>
                                                <div class="h-2 flex-1 rounded bg-slate-700/60"></div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-emerald-500/50"></div>
                                                <div class="h-2 flex-1 rounded bg-slate-700/60"></div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-amber-500/50"></div>
                                                <div class="h-2 w-3/4 rounded bg-slate-700/60"></div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="h-2.5 w-2.5 rounded-full bg-cyan-500/50"></div>
                                                <div class="h-2 w-1/2 rounded bg-slate-700/60"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- SOCIAL PROOF LOGOS --}}
    {{-- ============================================================ --}}
    <section
        class="relative py-14 border-y border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/30">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <p
                class="text-center text-xs font-medium uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-8">
                Confian en nosotros empresas en todo Ecuador</p>
            <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
                @foreach (['Quito', 'Guayaquil', 'Cuenca', 'Ambato', 'Manta', 'Loja'] as $city)
                    <div class="flex items-center gap-2 text-slate-300 dark:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span class="text-sm font-medium">{{ $city }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FEATURES --}}
    {{-- ============================================================ --}}
    <section id="funcionalidades" class="relative py-24 sm:py-32">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            {{-- Section header --}}
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400">
                    Funcionalidades</p>
                <h2
                    class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Todo lo que el SRI requiere, nada que te sobre
                </h2>
                <p class="mt-4 text-base text-slate-500 dark:text-slate-400 leading-relaxed">
                    Desarrollado especificamente para la normativa tributaria ecuatoriana. Cada funcionalidad esta
                    pensada para ahorrarte tiempo y evitar errores.
                </p>
            </div>

            {{-- Feature grid: 2 col asymmetric --}}
            <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Feature 1: Large --}}
                <div
                    class="lg:col-span-2 group relative rounded-2xl bg-slate-50 dark:bg-slate-900/60 p-8 sm:p-10 ring-1 ring-slate-200/60 dark:ring-slate-800/60 transition-all hover:ring-slate-300 dark:hover:ring-slate-700">
                    <div class="flex items-start gap-5">
                        <div
                            class="shrink-0 flex h-11 w-11 items-center justify-center rounded-xl bg-teal-600 text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Todos los documentos
                                electronicos</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                Facturas, notas de credito y debito, comprobantes de retencion, guias de remision y
                                liquidaciones de compra. Cumple con la ficha tecnica del SRI al 100%.
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-3 gap-3">
                        @foreach (['Facturas', 'Notas de credito', 'Retenciones', 'Guias de remision', 'Notas de debito', 'Liquidaciones'] as $doc)
                            <div
                                class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800/50 rounded-lg px-3 py-2 ring-1 ring-slate-200/60 dark:ring-slate-700/40">
                                <svg class="h-3.5 w-3.5 text-teal-500 shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $doc }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Feature 2 --}}
                <div
                    class="group relative rounded-2xl bg-slate-50 dark:bg-slate-900/60 p-8 ring-1 ring-slate-200/60 dark:ring-slate-800/60 transition-all hover:ring-slate-300 dark:hover:ring-slate-700">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">Firma electronica XAdES-BES
                    </h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Sube tu certificado .p12 una sola vez. Cada documento se firma automaticamente con el estandar
                        que exige el SRI.
                    </p>
                </div>

                {{-- Feature 3 --}}
                <div
                    class="group relative rounded-2xl bg-slate-50 dark:bg-slate-900/60 p-8 ring-1 ring-slate-200/60 dark:ring-slate-800/60 transition-all hover:ring-slate-300 dark:hover:ring-slate-700">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">Autorizacion en segundos</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Envio automatico al SRI en tiempo real. Recibe la clave de autorizacion sin intervencion manual
                        ni demoras.
                    </p>
                </div>

                {{-- Feature 4 --}}
                <div
                    class="group relative rounded-2xl bg-slate-50 dark:bg-slate-900/60 p-8 ring-1 ring-slate-200/60 dark:ring-slate-800/60 transition-all hover:ring-slate-300 dark:hover:ring-slate-700">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-500 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">Envio automatico por email
                    </h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Tus clientes reciben el RIDE en PDF y el XML al momento de la autorizacion. Personaliza el
                        diseño con tu marca.
                    </p>
                </div>

                {{-- Feature 5 --}}
                <div
                    class="group relative rounded-2xl bg-slate-50 dark:bg-slate-900/60 p-8 ring-1 ring-slate-200/60 dark:ring-slate-800/60 transition-all hover:ring-slate-300 dark:hover:ring-slate-700">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">Reportes y ATS</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Genera reportes de ventas, retenciones e IVA. Exporta el Anexo Transaccional Simplificado listo
                        para el SRI.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- HOW IT WORKS --}}
    {{-- ============================================================ --}}
    <section
        class="relative py-24 sm:py-32 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-100 dark:border-slate-800/40">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <p class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400">Como
                    funciona</p>
                <h2
                    class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                    De cero a facturando en 5 minutos
                </h2>
            </div>

            <div class="mt-16 grid gap-8 sm:grid-cols-3">
                <div class="relative text-center">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200/60 dark:ring-slate-700/60">
                        <span class="font-display text-xl font-bold text-teal-600 dark:text-teal-400">1</span>
                    </div>
                    <h3 class="mt-5 text-base font-semibold text-slate-900 dark:text-white">Crea tu cuenta</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Registrate con tu RUC y datos basicos.
                        Configura tu establecimiento y punto de emision.</p>
                </div>
                <div class="relative text-center">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200/60 dark:ring-slate-700/60">
                        <span class="font-display text-xl font-bold text-teal-600 dark:text-teal-400">2</span>
                    </div>
                    <h3 class="mt-5 text-base font-semibold text-slate-900 dark:text-white">Sube tu firma electronica
                    </h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Carga tu archivo .p12 y nosotros nos
                        encargamos de firmar cada documento automaticamente.</p>
                </div>
                <div class="relative text-center">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200/60 dark:ring-slate-700/60">
                        <span class="font-display text-xl font-bold text-teal-600 dark:text-teal-400">3</span>
                    </div>
                    <h3 class="mt-5 text-base font-semibold text-slate-900 dark:text-white">Emite tus documentos</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Crea facturas en segundos. Se firman,
                        envian al SRI y se autorizan automaticamente.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- PRICING --}}
    {{-- ============================================================ --}}
    @if ($plans->count() > 0)
        <section id="planes" class="relative py-24 sm:py-32" x-data="{ yearly: false }">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="text-center max-w-2xl mx-auto">
                    <p class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400">Planes
                    </p>
                    <h2
                        class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Precios transparentes, sin sorpresas
                    </h2>
                    <p class="mt-4 text-base text-slate-500 dark:text-slate-400">
                        Sin comisiones por documento. Escoge el plan que se ajuste a tu negocio.
                    </p>
                </div>

                {{-- Billing toggle --}}
                @if ($plans->contains(fn($p) => $p->price_yearly > 0))
                    <div class="mt-10 flex items-center justify-center gap-3">
                        <span class="text-sm font-medium"
                            :class="!yearly ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'">Mensual</span>
                        <button @click="yearly = !yearly" class="relative h-6 w-11 rounded-full transition-colors"
                            :class="yearly ? 'bg-teal-600' : 'bg-slate-300 dark:bg-slate-600'">
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform"
                                :class="yearly && 'translate-x-5'"></span>
                        </button>
                        @php $maxSavings = $plans->max(fn($p) => $p->getYearlySavingsPercent()); @endphp
                        <span class="text-sm font-medium"
                            :class="yearly ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'">
                            Anual
                            @if ($maxSavings > 0)
                                <span
                                    class="ml-1 inline-flex rounded-full bg-teal-50 dark:bg-teal-500/10 px-2 py-0.5 text-[10px] font-bold text-teal-700 dark:text-teal-400">Ahorra
                                    hasta {{ $maxSavings }}%</span>
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Plan cards grid --}}
                <div
                    class="mt-12 grid gap-6 {{ $plans->count() >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-' . min($plans->count(), 3) }}">
                    @foreach ($plans as $plan)
                        @if ($plan->is_featured)
                            {{-- Featured plan: dark card --}}
                            <div
                                class="relative rounded-2xl bg-slate-900 dark:bg-white/[0.03] p-7 ring-1 ring-slate-800 dark:ring-slate-700 shadow-xl shadow-slate-900/10 dark:shadow-black/20">
                                <div class="absolute -top-3 left-6">
                                    <span
                                        class="inline-flex rounded-full bg-teal-500 px-3.5 py-1 text-[11px] font-semibold text-white shadow-sm">
                                        Mas popular
                                    </span>
                                </div>
                                <h3 class="font-display text-lg font-semibold text-white">{{ $plan->name }}</h3>
                                <p class="mt-1.5 text-sm text-slate-400">{{ $plan->description }}</p>
                                <div class="mt-6 flex items-baseline gap-1">
                                    <span class="font-display text-4xl font-bold text-white"
                                        x-text="yearly ? '${{ $plan->price_yearly }}' : '${{ $plan->price_monthly }}'">
                                        ${{ $plan->price_monthly }}
                                    </span>
                                    <span class="text-sm text-slate-500" x-text="yearly ? '/ano' : '/mes'">/mes</span>
                                </div>
                                <a href="{{ route('register') }}?plan={{ $plan->slug }}"
                                    class="mt-6 flex w-full items-center justify-center rounded-xl bg-teal-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-teal-700 shadow-sm shadow-teal-600/30">
                                    Comenzar ahora
                                </a>
                                <ul class="mt-7 space-y-3">
                                    @foreach ($plan->getFeaturesList() as $feature)
                                        <li class="flex items-start gap-2.5 text-sm text-slate-300">
                                            <svg class="h-4 w-4 mt-0.5 shrink-0 text-teal-400" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            {{-- Regular plan: white card --}}
                            <div
                                class="relative rounded-2xl bg-white dark:bg-slate-900 p-7 ring-1 ring-slate-200 dark:ring-slate-800 transition-shadow hover:shadow-lg hover:shadow-slate-200/40 dark:hover:shadow-slate-900/40">
                                <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ $plan->name }}</h3>
                                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ $plan->description }}
                                </p>
                                <div class="mt-6 flex items-baseline gap-1">
                                    <span class="font-display text-4xl font-bold text-slate-900 dark:text-white"
                                        x-text="yearly ? '${{ $plan->price_yearly }}' : '${{ $plan->price_monthly }}'">
                                        ${{ $plan->price_monthly }}
                                    </span>
                                    <span class="text-sm text-slate-400" x-text="yearly ? '/ano' : '/mes'">/mes</span>
                                </div>
                                <a href="{{ route('register') }}?plan={{ $plan->slug }}"
                                    class="mt-6 flex w-full items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 py-3 text-sm font-semibold text-slate-900 dark:text-white transition-colors hover:bg-slate-200 dark:hover:bg-slate-700">
                                    Comenzar ahora
                                </a>
                                <ul class="mt-7 space-y-3">
                                    @foreach ($plan->getFeaturesList() as $feature)
                                        <li
                                            class="flex items-start gap-2.5 text-sm text-slate-600 dark:text-slate-300">
                                            <svg class="h-4 w-4 mt-0.5 shrink-0 text-teal-500" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>

                <p class="mt-8 text-center text-sm text-slate-400 dark:text-slate-500">
                    Todos los planes incluyen soporte por email. Pago seguro por transferencia bancaria.
                </p>
            </div>
        </section>
    @endif

    {{-- ============================================================ --}}
    {{-- TESTIMONIALS --}}
    {{-- ============================================================ --}}
    <section id="testimonios"
        class="relative py-24 sm:py-32 bg-slate-50 dark:bg-slate-900/30 border-y border-slate-100 dark:border-slate-800/40">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400">Testimonios
                </p>
                <h2
                    class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Lo que dicen quienes ya facturan con nosotros
                </h2>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $testimonials = [
                        [
                            'text' =>
                                'Antes facturaba en un sistema que se caia cada fin de mes. Con AmePhia emito mis documentos en segundos y nunca he tenido problemas de disponibilidad. El soporte responde inmediatamente.',
                            'name' => 'Maria Gonzalez',
                            'role' => 'Gerente, Comercial Express',
                            'city' => 'Quito',
                            'initials' => 'MG',
                            'color' => 'from-teal-500 to-emerald-500',
                        ],
                        [
                            'text' =>
                                'Como contador manejo la facturacion de 12 clientes. La gestion multi-empresa me permite cambiar entre ellos sin cerrar sesion. El ATS me ahorra horas cada mes.',
                            'name' => 'Carlos Mendoza',
                            'role' => 'Contador, Mendoza & Asociados',
                            'city' => 'Guayaquil',
                            'initials' => 'CM',
                            'color' => 'from-violet-500 to-indigo-500',
                        ],
                        [
                            'text' =>
                                'Integre mi tienda online con la API en una tarde. Cada venta genera automaticamente su factura electronica, se firma y se envia al cliente. Es transparente.',
                            'name' => 'Ana Rodriguez',
                            'role' => 'CTO, TechStore EC',
                            'city' => 'Cuenca',
                            'initials' => 'AR',
                            'color' => 'from-amber-500 to-orange-500',
                        ],
                    ];
                @endphp

                @foreach ($testimonials as $t)
                    <div
                        class="rounded-2xl bg-white dark:bg-slate-900 p-7 ring-1 ring-slate-200/60 dark:ring-slate-800/60">
                        <div class="flex gap-0.5 mb-4">
                            @for ($i = 0; $i < 5; $i++)
                                <svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">"{{ $t['text'] }}"
                        </p>
                        <div
                            class="mt-6 flex items-center gap-3 pt-5 border-t border-slate-100 dark:border-slate-800/60">
                            <div
                                class="h-10 w-10 rounded-full bg-gradient-to-br {{ $t['color'] }} flex items-center justify-center text-xs font-bold text-white">
                                {{ $t['initials'] }}</div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $t['name'] }}
                                </p>
                                <p class="text-xs text-slate-400">{{ $t['role'] }} — {{ $t['city'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FAQ --}}
    {{-- ============================================================ --}}
    <section id="faq" class="relative py-24 sm:py-32">
        <div class="mx-auto max-w-3xl px-5 sm:px-8">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400">Preguntas
                    frecuentes</p>
                <h2
                    class="mt-3 font-display text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Resolvemos tus dudas
                </h2>
            </div>

            <div class="mt-14 space-y-3" x-data="{ openFaq: null }">
                @php
                    $faqs = [
                        [
                            'q' => 'Que necesito para empezar a facturar electronicamente?',
                            'a' =>
                                'Necesitas tu RUC activo, una firma electronica vigente (archivo .p12 del Banco Central, Security Data o ANF) y estar habilitado para facturacion electronica en el portal del SRI. Si aun no estas habilitado, te guiamos paso a paso en el proceso.',
                        ],
                        [
                            'q' => 'El sistema esta autorizado por el SRI?',
                            'a' =>
                                'Si. Nuestro sistema cumple al 100% con la ficha tecnica de comprobantes electronicos del SRI. Generamos el XML en el formato exigido, firmamos con XAdES-BES y nos comunicamos directamente con los web services del SRI para la recepcion y autorizacion de documentos.',
                        ],
                        [
                            'q' => 'Puedo migrar desde otro sistema de facturacion?',
                            'a' =>
                                'Si. Puedes importar tu catalogo de productos y clientes desde un archivo CSV o Excel. Tu secuencial de facturacion continua donde lo dejaste en tu sistema anterior. No pierdes historico.',
                        ],
                        [
                            'q' => 'Que pasa si el SRI esta caido o no responde?',
                            'a' =>
                                'Nuestro sistema tiene un mecanismo de reintentos automaticos. Si el SRI no responde, tu documento queda en cola y se envia automaticamente cuando el servicio se restablece. Tambien puedes ver el estado de cada documento en tiempo real desde tu panel.',
                        ],
                        [
                            'q' => 'Puedo emitir facturas desde mi celular?',
                            'a' =>
                                'Si. La plataforma es totalmente responsive y funciona desde cualquier navegador movil. Ademas, estamos desarrollando una aplicacion nativa para iOS y Android que estara disponible proximamente.',
                        ],
                        [
                            'q' => 'Como funciona el registro?',
                            'a' =>
                                'Registrate en 2 minutos, selecciona el plan que mejor se adapte a tu negocio y realiza el pago por transferencia bancaria. Una vez confirmado el pago, tu cuenta se activa inmediatamente con todas las funcionalidades del plan seleccionado.',
                        ],
                        [
                            'q' => 'El sistema genera el ATS (Anexo Transaccional Simplificado)?',
                            'a' =>
                                'Si. Desde la seccion de reportes puedes generar el ATS de cualquier mes. El archivo XML se genera automaticamente con toda la informacion de ventas y retenciones del periodo seleccionado, listo para subir al portal del SRI.',
                        ],
                        [
                            'q' => 'Puedo conectar mi sistema con la API?',
                            'a' =>
                                'Si. Ofrecemos una API REST documentada que te permite crear documentos, consultar clientes, productos y reportes desde cualquier sistema externo. Disponible en los planes Profesional y Empresarial.',
                        ],
                    ];
                @endphp

                @foreach ($faqs as $index => $faq)
                    <div class="rounded-xl ring-1 transition-all"
                        :class="openFaq === {{ $index }} ?
                            'ring-teal-200 dark:ring-teal-800/60 bg-teal-50/30 dark:bg-teal-500/5' :
                            'ring-slate-200 dark:ring-slate-800 bg-white dark:bg-slate-900/40 hover:ring-slate-300 dark:hover:ring-slate-700'">
                        <button @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left">
                            <span class="text-sm font-semibold leading-snug"
                                :class="openFaq === {{ $index }} ? 'text-teal-700 dark:text-teal-300' :
                                    'text-slate-900 dark:text-white'">
                                {{ $faq['q'] }}
                            </span>
                            <svg class="h-4.5 w-4.5 shrink-0 text-slate-400 transition-transform duration-200"
                                :class="openFaq === {{ $index }} && 'rotate-45'" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                        <div x-show="openFaq === {{ $index }}" x-cloak x-collapse>
                            <div class="px-6 pb-5">
                                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                    {{ $faq['a'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <p class="text-sm text-slate-400 dark:text-slate-500">
                    Tienes otra pregunta?
                    <a href="mailto:soporte@amephia.com"
                        class="font-medium text-teal-600 dark:text-teal-400 hover:underline">Escribenos</a>
                </p>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- CTA --}}
    {{-- ============================================================ --}}
    <section class="relative py-24 sm:py-32 bg-slate-900 dark:bg-slate-950 noise overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-teal-500/10 rounded-full blur-3xl">
        </div>
        <div class="relative mx-auto max-w-3xl px-5 sm:px-8 text-center">
            <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-white">
                Empieza a facturar hoy
            </h2>
            <p class="mt-4 text-base sm:text-lg text-slate-400 max-w-xl mx-auto">
                Registrate en 2 minutos. Sin contratos a largo plazo. Pago seguro por transferencia bancaria.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('register') }}"
                    class="inline-flex items-center gap-2 rounded-full bg-teal-600 hover:bg-teal-500 px-7 py-3.5 text-sm font-semibold text-white transition-all shadow-lg shadow-teal-600/25 hover:shadow-xl hover:shadow-teal-500/30">
                    Crear cuenta gratis
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="mailto:ventas@amephia.com"
                    class="inline-flex items-center gap-2 px-6 py-3.5 text-sm font-medium text-slate-300 transition-colors hover:text-white">
                    Contactar ventas
                </a>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FOOTER --}}
    {{-- ============================================================ --}}
    <footer class="bg-white dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800/60">
        <div class="mx-auto max-w-6xl px-5 sm:px-8 py-14">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
                {{-- Brand --}}
                <div class="lg:col-span-4">
                    <a href="/" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/app_icon_transparent.png') }}" alt=""
                            class="h-7 w-7 object-contain dark:hidden">
                        <img src="{{ asset('images/app_icon_transparent.png') }}" alt=""
                            class="h-7 w-7 object-contain hidden dark:block">
                        <span
                            class="font-display font-bold text-lg tracking-tight text-slate-900 dark:text-white">AmePhia<span
                                class="text-teal-600 dark:text-teal-400">.</span></span>
                    </a>
                    <p class="mt-3 text-sm text-slate-400 dark:text-slate-500 leading-relaxed max-w-xs">
                        Facturacion electronica autorizada por el SRI. Un producto de
                        <a href="https://amephia.com" target="_blank" rel="noopener"
                            class="text-teal-600 dark:text-teal-400 hover:underline">AmePhia Systems Inc.</a>
                    </p>
                </div>

                {{-- Links --}}
                <div class="lg:col-span-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-900 dark:text-white">Producto
                    </h4>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="#funcionalidades"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Funcionalidades</a>
                        </li>
                        <li><a href="#planes"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Precios</a>
                        </li>
                        <li><a href="#faq"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Preguntas
                                frecuentes</a></li>
                        <li><a href="#"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">API
                                y documentacion</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-900 dark:text-white">Soporte
                    </h4>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="#"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Centro
                                de ayuda</a></li>
                        <li><a href="mailto:soporte@amephia.com"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Contacto</a>
                        </li>
                        <li><a href="#"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Estado
                                del servicio</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-900 dark:text-white">Legal
                    </h4>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="#"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Terminos
                                de servicio</a></li>
                        <li><a href="#"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Politica
                                de privacidad</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-900 dark:text-white">Empresa
                    </h4>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="https://amephia.com" target="_blank" rel="noopener"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">AmePhia
                                Systems</a></li>
                        <li><a href="https://amephia.com" target="_blank" rel="noopener"
                                class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Otros
                                productos</a></li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-12 pt-8 border-t border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    &copy; {{ date('Y') }} AmePhia Systems Inc. Todos los derechos reservados.
                </p>
                <div class="flex items-center gap-1 text-xs text-slate-300 dark:text-slate-600">
                    <span>Hecho en</span>
                    <svg class="h-3.5 w-5" viewBox="0 0 20 14" fill="none">
                        <rect width="20" height="4.67" fill="#FFD100" />
                        <rect y="4.67" width="20" height="2.33" fill="#003DA5" />
                        <rect y="7" width="20" height="7" fill="#CE1126" />
                    </svg>
                    <span>Ecuador</span>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>
