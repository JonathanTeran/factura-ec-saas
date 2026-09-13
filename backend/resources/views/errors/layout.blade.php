<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0B1220">
    <title>@yield('title', 'Error') · Facturón</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&display=swap" rel="stylesheet">
    @stack('head')
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0; min-height: 100%; display: flex; flex-direction: column;
            background: #0B1220 radial-gradient(70% 60% at 85% -10%, rgba(43,84,228,.38), transparent 60%);
            color: #E2E8F0; -webkit-font-smoothing: antialiased;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .flag { height: 4px; background: linear-gradient(90deg, #FFCE00 0 33.3%, #0653C6 33.3% 66.6%, #EF3340 66.6% 100%); }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 20px; }
        .card { width: 100%; max-width: 560px; text-align: center; }
        .brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
        .brand svg { width: 40px; height: 40px; }
        .brand span { font-family: "Bricolage Grotesque", ui-sans-serif, system-ui, sans-serif; font-weight: 700; font-size: 22px; letter-spacing: -.01em; }
        .code {
            margin: 34px 0 6px; font-family: "Bricolage Grotesque", ui-sans-serif, system-ui, sans-serif;
            font-weight: 800; font-size: clamp(64px, 16vw, 112px); line-height: .95; letter-spacing: -.04em;
            background: linear-gradient(180deg, #FFFFFF 0%, rgba(255,255,255,.55) 100%); -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .code.small { font-size: clamp(40px, 9vw, 64px); }
        h1 {
            margin: 6px 0 0; font-family: "Bricolage Grotesque", ui-sans-serif, system-ui, sans-serif;
            font-weight: 800; font-size: clamp(24px, 4.6vw, 32px); line-height: 1.15; letter-spacing: -.02em; color: #fff;
        }
        .lead { margin: 14px auto 0; max-width: 460px; font-size: 16px; line-height: 1.6; color: #B7C2D6; }
        .lead code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 14px; background: rgba(255,255,255,.08); padding: 2px 6px; border-radius: 6px; color: #E2E8F0; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 28px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 44px; padding: 0 22px;
            border-radius: 999px; font-weight: 600; font-size: 15px; text-decoration: none; transition: background-color .15s, border-color .15s;
        }
        .btn-primary { background: #2B54E4; color: #fff; box-shadow: 0 10px 30px rgba(43,84,228,.35); }
        .btn-primary:hover { background: #2446C4; }
        .btn-secondary { border: 1px solid rgba(255,255,255,.22); color: #fff; }
        .btn-secondary:hover { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.4); }
        .support { margin: 34px 0 0; font-size: 13.5px; color: #8B98AF; line-height: 1.7; }
        .support a { color: #C7D2FE; text-decoration: none; }
        .support a:hover { text-decoration: underline; }
        .hint { margin: 18px auto 0; max-width: 460px; padding: 12px 14px; border-radius: 12px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); font-size: 13.5px; color: #B7C2D6; text-align: left; }
        footer { padding: 18px 20px 26px; text-align: center; font-size: 12.5px; color: #6B7791; }
        footer a { color: #8B98AF; text-decoration: none; }
        @media (prefers-reduced-motion: no-preference) {
            .card { animation: rise .5s ease-out both; }
            @keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        }
    </style>
</head>
<body>
    <div class="flag" aria-hidden="true"></div>
    <main>
        <div class="card">
            <a class="brand" href="{{ $homeUrl }}" aria-label="Facturón, ir al inicio">
                <svg viewBox="0 0 1024 1024" aria-hidden="true" focusable="false">
                    <defs>
                        <linearGradient id="flag-grad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#FFCE00"/><stop offset="48%" stop-color="#0653C6"/><stop offset="100%" stop-color="#EF3340"/>
                        </linearGradient>
                    </defs>
                    <rect width="1024" height="1024" rx="230" fill="url(#flag-grad)"/>
                    <rect x="110" y="110" width="804" height="804" rx="190" fill="#0B1424"/>
                    <rect x="400" y="300" width="100" height="424" rx="16" fill="#FFFFFF"/>
                    <rect x="400" y="300" width="260" height="100" rx="16" fill="#FFFFFF"/>
                    <rect x="400" y="440" width="200" height="96" rx="16" fill="#FFFFFF"/>
                </svg>
                <span>Facturón</span>
            </a>

            <p class="code @yield('code_class')" aria-hidden="true">@yield('code')</p>
            <h1>@yield('heading')</h1>
            <p class="lead">@yield('message')</p>

            @hasSection('hint')
                <div class="hint">@yield('hint')</div>
            @endif

            <div class="actions">
                @yield('actions')
            </div>

            <p class="support">
                ¿Necesitas ayuda?
                @if($supportWhatsapp)
                    <a href="https://wa.me/{{ $supportWhatsapp }}" target="_blank" rel="noopener">WhatsApp {{ $whatsappPretty }}</a> ·
                @endif
                <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </p>
        </div>
    </main>
    <footer>© {{ date('Y') }} Facturón · Un producto de <a href="{{ config('support.company_url') }}" target="_blank" rel="noopener">{{ config('support.company') }}</a></footer>
</body>
</html>
