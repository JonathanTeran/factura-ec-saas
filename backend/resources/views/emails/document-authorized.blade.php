<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->document_type->label() }} - {{ $company->business_name }}</title>
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
        .document-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #059669;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        .greeting {
            font-size: 15px;
            margin-bottom: 16px;
        }
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
        .info-table td:first-child {
            color: #6b7280;
            width: 40%;
        }
        .info-table td:last-child {
            color: #111827;
            font-weight: 500;
            text-align: right;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .totals-table td {
            padding: 6px 0;
            font-size: 14px;
        }
        .totals-table td:first-child {
            color: #6b7280;
        }
        .totals-table td:last-child {
            text-align: right;
            color: #111827;
        }
        .totals-table .total-row td {
            font-weight: 700;
            font-size: 16px;
            color: #111827;
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
        }
        .access-key {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            margin: 16px 0;
        }
        .access-key label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .access-key code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #111827;
            word-break: break-all;
        }
        .portal-cta {
            text-align: center;
            margin: 24px 0;
            padding: 20px;
            background: #f0f9ff;
            border-radius: 8px;
            border: 1px solid #bae6fd;
        }
        .portal-cta p {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #0369a1;
        }
        .portal-cta a {
            display: inline-block;
            background: #0284c7;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            color: #9ca3af;
            font-size: 12px;
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ $company->business_name }}</h2>
            <p>RUC: {{ $company->ruc }}</p>
            <span class="document-badge">Documento Autorizado</span>
        </div>

        <p class="greeting">
            Estimado/a <strong>{{ $customer->business_name ?? $customer->name }}</strong>,
        </p>
        <p style="font-size: 14px; color: #374151;">
            Le informamos que su {{ strtolower($document->document_type->label()) }} ha sido autorizado/a por el Servicio de Rentas Internas (SRI).
            Adjunto encontrará el RIDE (PDF) y el archivo XML del documento.
        </p>

        <table class="info-table">
            <tr>
                <td>Tipo de documento</td>
                <td>{{ $document->document_type->label() }}</td>
            </tr>
            <tr>
                <td>Número</td>
                <td>{{ $document->getDocumentNumber() }}</td>
            </tr>
            <tr>
                <td>Fecha de emisión</td>
                <td>{{ $document->issue_date->format('d/m/Y') }}</td>
            </tr>
            @if($document->authorization_number)
            <tr>
                <td>No. Autorización</td>
                <td style="font-size: 12px;">{{ $document->authorization_number }}</td>
            </tr>
            @endif
        </table>

        <table class="totals-table">
            @if($document->subtotal_0 > 0)
            <tr>
                <td>Subtotal 0%</td>
                <td>${{ number_format($document->subtotal_0, 2) }}</td>
            </tr>
            @endif
            @if($document->subtotal_12 > 0)
            <tr>
                <td>Subtotal 12%</td>
                <td>${{ number_format($document->subtotal_12, 2) }}</td>
            </tr>
            @endif
            @if($document->subtotal_15 > 0)
            <tr>
                <td>Subtotal 15%</td>
                <td>${{ number_format($document->subtotal_15, 2) }}</td>
            </tr>
            @endif
            @if($document->subtotal_5 > 0)
            <tr>
                <td>Subtotal 5%</td>
                <td>${{ number_format($document->subtotal_5, 2) }}</td>
            </tr>
            @endif
            @if($document->total_discount > 0)
            <tr>
                <td>Descuento</td>
                <td>-${{ number_format($document->total_discount, 2) }}</td>
            </tr>
            @endif
            @if($document->total_tax > 0)
            <tr>
                <td>IVA</td>
                <td>${{ number_format($document->total_tax, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td>${{ number_format($document->total, 2) }}</td>
            </tr>
        </table>

        @if($document->access_key)
        <div class="access-key">
            <label>Clave de Acceso</label>
            <code>{{ $document->access_key }}</code>
        </div>
        @endif

        <div class="portal-cta">
            <p>Accede a todos tus documentos electrónicos en tu portal:</p>
            <a href="{{ $portalUrl }}">Ver en Portal</a>
        </div>

        <div class="footer">
            <p>Este documento fue generado electrónicamente y tiene plena validez tributaria.</p>
            <p>{{ $company->business_name }} - {{ $company->address ?? '' }}</p>
        </div>
    </div>
</body>
</html>
