<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; -webkit-text-size-adjust: none; }
        table { border-collapse: collapse; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
        .wrap { width: 100%; background-color: #f1f5f9; }
        .card { width: 570px; max-width: 100%; }
        .band { background-color: #0b1220; padding: 22px 32px; }
        .content {
            background-color: #ffffff; padding: 32px; border: 1px solid #e2e8f0; border-top: 0;
            border-radius: 0 0 16px 16px; color: #334155; font-size: 15px; line-height: 1.6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .header { margin: 0 0 22px; }
        .header h2 { margin: 0 0 6px; font-size: 24px; line-height: 1.2; color: #0b1220; font-weight: 800; letter-spacing: -0.01em; }
        .header p { margin: 0; font-size: 14px; color: #64748b; }
        .badge { display: inline-block; margin-top: 12px; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.02em; }
        .badge-success { background: #ecfdf5; color: #047857; }
        .badge-danger { background: #fef2f2; color: #b91c1c; }
        .badge-warning { background: #fffbeb; color: #b45309; }
        .badge-info { background: #eef2ff; color: #2b54e4; }
        .greeting { font-size: 16px; margin: 0 0 14px; color: #0b1220; }
        .text { font-size: 15px; color: #334155; margin: 0 0 14px; }
        .info-table { width: 100%; margin: 18px 0; }
        .info-table td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .info-table td:first-child { color: #64748b; width: 42%; }
        .info-table td:last-child { color: #0b1220; font-weight: 600; text-align: right; }
        .cta { text-align: center; margin: 26px 0 8px; }
        .cta a {
            display: inline-block; color: #ffffff !important; text-decoration: none; padding: 14px 30px;
            border-radius: 999px; font-weight: 700; font-size: 15px; background-color: #2b54e4;
        }
        .cta-primary { background-color: #2b54e4 !important; }
        .cta-success { background-color: #059669 !important; }
        .cta-danger { background-color: #dc2626 !important; }
        .cta-warning { background-color: #d97706 !important; }
        .alert-box { padding: 14px 16px; border-radius: 12px; margin: 16px 0; font-size: 14px; line-height: 1.55; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .alert-info { background: #eef2ff; border: 1px solid #c7d2fe; color: #1e3a8a; }
        .footer { margin-top: 26px; padding-top: 18px; border-top: 1px solid #e2e8f0; text-align: center; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 4px 0; line-height: 1.5; }
        .footer a { color: #64748b; }
        @media only screen and (max-width: 600px) {
            .content { padding: 22px !important; }
            .band { padding: 18px 22px !important; }
            .info-table td:first-child { width: 48% !important; }
        }
    </style>
</head>
<body>
<table class="wrap" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" style="padding: 28px 12px;">
    <table class="card" width="570" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td style="padding: 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td width="34%" bgcolor="#FFCE00" style="height: 4px; font-size: 0; line-height: 0; padding: 0; border-radius: 16px 0 0 0;">&nbsp;</td>
                        <td width="33%" bgcolor="#0653C6" style="height: 4px; font-size: 0; line-height: 0; padding: 0;">&nbsp;</td>
                        <td width="33%" bgcolor="#EF3340" style="height: 4px; font-size: 0; line-height: 0; padding: 0; border-radius: 0 16px 0 0;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="band">
                <a href="{{ config('app.url') }}" style="display: inline-block;">
                    <img src="{{ rtrim(config('app.url'), '/') }}/marketing/email-logo.png" alt="Facturón" width="158" height="48" style="display: block; width: 158px; height: 48px;">
                </a>
            </td>
        </tr>
        <tr>
            <td class="content">
                @yield('content')

                <div class="footer">
                    @yield('footer')
                    <p>Facturón · Facturación electrónica del Ecuador · <a href="https://facturon.ec">facturon.ec</a></p>
                    <p>Correo automático. Para ayuda escribe a <a href="mailto:{{ config('support.email') }}">{{ config('support.email') }}</a>.</p>
                </div>
            </td>
        </tr>
    </table>
    @include('emails.partials.promo')
</td>
</tr>
</table>
</body>
</html>
