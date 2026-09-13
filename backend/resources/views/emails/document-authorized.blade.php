@php
    $accent = preg_match('/^#[0-9a-fA-F]{3,6}$/', $mailTemplate['accent_color'] ?? '') ? $mailTemplate['accent_color'] : '#2b54e4';
    $assetBase = rtrim(config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $mailTemplate['subject'] }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; -webkit-text-size-adjust: none; }
        table { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; }
        .card { width: 570px; max-width: 100%; }
        .band { background-color: {{ $accent }}; padding: 26px 32px 22px; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .band h1 { margin: 0; font-size: 22px; line-height: 1.25; font-weight: 800; color: #ffffff; letter-spacing: -0.01em; }
        .band p { margin: 6px 0 0; font-size: 14px; color: #ffffff; opacity: 0.85; }
        .band .badge { display: inline-block; margin-top: 14px; padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,0.18); font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #ffffff; }
        .content { background-color: #ffffff; padding: 30px 32px; border: 1px solid #e2e8f0; border-top: 0; border-radius: 0 0 16px 16px; color: #334155; font-size: 15px; line-height: 1.65; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .content p { margin: 0 0 14px; }
        .summary { width: 100%; margin: 20px 0; }
        .summary td { border-bottom: 1px solid #f1f5f9; padding: 10px 0; font-size: 14px; vertical-align: top; }
        .summary td:first-child { color: #64748b; white-space: nowrap; padding-right: 16px; width: 1%; }
        .summary td:last-child { color: #0b1220; font-weight: 600; text-align: right; word-break: break-word; }
        .summary td.mono { font-family: Menlo, Consolas, 'Courier New', monospace; font-size: 12px; font-weight: 600; word-break: break-all; }
        .access-key { margin: 20px 0; padding: 14px 16px; border-radius: 12px; background: #0b1220; }
        .access-key .label { color: #94a3b8; font-size: 11px; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 6px; }
        .access-key code { font-family: Menlo, Consolas, 'Courier New', monospace; font-size: 12px; word-break: break-all; color: #6ee7b7; }
        .attachments { margin: 20px 0; padding: 14px 16px; border-radius: 12px; background: #eef2ff; border: 1px solid #c7d2fe; }
        .attachments p { margin: 0 0 6px; color: #1e3a8a; font-weight: 700; font-size: 14px; }
        .attachments ul { margin: 0; padding-left: 18px; color: #1e3a8a; font-size: 14px; }
        .cta { margin: 26px 0 8px; text-align: center; }
        .cta a { display: inline-block; background-color: {{ $accent }}; color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 999px; font-weight: 700; font-size: 15px; }
        .footer { margin-top: 26px; padding-top: 18px; border-top: 1px solid #e2e8f0; font-size: 12px; line-height: 1.6; color: #64748b; }
        .footer p { margin: 0 0 6px; }
        .sent-with { text-align: center; color: #94a3b8; font-size: 12px; margin-top: 8px; }
        .sent-with a { color: #64748b; }
        @media only screen and (max-width: 600px) {
            .content { padding: 22px !important; }
            .band { padding: 20px 22px 18px !important; }
        }
    </style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f1f5f9;">
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
                <h1>{{ $mailTemplate['header_title'] }}</h1>
                @if(!empty($mailTemplate['header_subtitle']))
                    <p>{{ $mailTemplate['header_subtitle'] }}</p>
                @endif
                @if(!empty($mailTemplate['badge_text']))
                    <span class="badge">{{ $mailTemplate['badge_text'] }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="content">
                {!! $mailTemplate['body_html'] !!}

                <table class="summary" role="presentation">
                    @foreach($summaryRows as $label => $value)
                        @if(filled($value))
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="{{ mb_strlen((string) $value) > 30 ? 'mono' : '' }}">{{ $value }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>

                @if(filled($accessKey))
                    <div class="access-key">
                        <div class="label">Clave de acceso</div>
                        <code>{{ $accessKey }}</code>
                    </div>
                @endif

                @if(!empty($attachmentNames))
                    <div class="attachments">
                        <p>Archivos adjuntos</p>
                        <ul>
                            @foreach($attachmentNames as $attachmentName)
                                <li>{{ $attachmentName }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($mailTemplate['show_portal_button'])
                    <div class="cta">
                        <a href="{{ $ctaUrl }}">{{ $mailTemplate['cta_label'] }}</a>
                    </div>
                @endif

                <div class="footer">
                    {!! $mailTemplate['footer_html'] !!}
                    <p>Este comprobante fue autorizado por el SRI. Correo automático: no responda a este mensaje.</p>
                </div>
                <p class="sent-with">Enviado con <a href="https://facturon.ec"><strong>Facturón</strong></a> · Facturación electrónica del Ecuador</p>
            </td>
        </tr>
    </table>
    @include('emails.partials.promo')
</td>
</tr>
</table>
</body>
</html>
