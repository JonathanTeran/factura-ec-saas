<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: #ffffff;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .header h2 {
            color: #111827;
            margin: 0 0 4px 0;
            font-size: 18px;
        }
        .header p {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        .badge-success { background: #ecfdf5; color: #059669; }
        .badge-danger { background: #fef2f2; color: #dc2626; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-info { background: #eff6ff; color: #2563eb; }
        .greeting {
            font-size: 15px;
            margin-bottom: 16px;
        }
        .text { font-size: 14px; color: #374151; }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .info-table td {
            padding: 8px 0;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-table td:first-child { color: #6b7280; width: 40%; }
        .info-table td:last-child { color: #111827; font-weight: 500; text-align: right; }
        .cta {
            text-align: center;
            margin: 24px 0;
        }
        .cta a {
            display: inline-block;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }
        .cta-primary { background: #0284c7; }
        .cta-danger { background: #dc2626; }
        .cta-warning { background: #d97706; }
        .cta-success { background: #059669; }
        .alert-box {
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
        }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .alert-info { background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; }
        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .footer p { color: #9ca3af; font-size: 12px; margin: 4px 0; }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')

        <div class="footer">
            @yield('footer')
            <p>{{ config('app.name') }} - Facturacion Electronica Ecuador</p>
            <p>Este es un correo automatico, por favor no responda directamente.</p>
        </div>
    </div>
</body>
</html>
