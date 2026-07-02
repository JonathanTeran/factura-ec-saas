<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailTemplate['subject'] }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            margin: 0;
            padding: 24px;
        }
        .shell {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .header {
            padding: 28px 32px 20px;
            color: #ffffff;
            background: {{ preg_match('/^#[0-9a-fA-F]{3,6}$/', $mailTemplate['accent_color'] ?? '') ? $mailTemplate['accent_color'] : '#1a73e8' }};
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.2;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            margin-top: 14px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .content {
            padding: 28px 32px;
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
        }
        .content p {
            margin: 0 0 14px;
        }
        .summary {
            width: 100%;
            margin: 22px 0;
            border-collapse: collapse;
        }
        .summary td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
            font-size: 14px;
        }
        .summary td:first-child {
            color: #6b7280;
            width: 42%;
        }
        .summary td:last-child {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }
        .access-key {
            margin: 22px 0;
            padding: 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }
        .access-key .label {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 6px;
        }
        .access-key code {
            font-size: 12px;
            word-break: break-all;
            color: #111827;
        }
        .attachments {
            margin: 22px 0;
            padding: 14px 16px;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        .attachments p {
            margin: 0 0 8px;
            color: #1d4ed8;
            font-weight: 600;
        }
        .attachments ul {
            margin: 0;
            padding-left: 18px;
            color: #1e3a8a;
        }
        .cta {
            margin: 26px 0 8px;
            text-align: center;
        }
        .cta a {
            display: inline-block;
            background: {{ preg_match('/^#[0-9a-fA-F]{3,6}$/', $mailTemplate['accent_color'] ?? '') ? $mailTemplate['accent_color'] : '#1a73e8' }};
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
        }
        .footer {
            padding: 0 32px 28px;
            font-size: 12px;
            line-height: 1.6;
            color: #6b7280;
        }
        .footer p {
            margin: 0 0 6px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="header">
            <h1>{{ $mailTemplate['header_title'] }}</h1>
            @if(!empty($mailTemplate['header_subtitle']))
                <p>{{ $mailTemplate['header_subtitle'] }}</p>
            @endif
            @if(!empty($mailTemplate['badge_text']))
                <span class="badge">{{ $mailTemplate['badge_text'] }}</span>
            @endif
        </div>

        <div class="content">
            {!! $mailTemplate['body_html'] !!}

            <table class="summary" role="presentation">
                @foreach($summaryRows as $label => $value)
                    @if(filled($value))
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $value }}</td>
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
                    <p>Archivos adjuntos incluidos</p>
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
        </div>

        <div class="footer">
            {!! $mailTemplate['footer_html'] !!}
            <p>{{ config('app.name') }} - Facturación Electrónica Ecuador</p>
            <p>Este es un correo automático, por favor no responda directamente.</p>
        </div>
    </div>
</body>
</html>
