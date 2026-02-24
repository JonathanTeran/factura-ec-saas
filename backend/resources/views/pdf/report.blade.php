<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte - {{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .container { max-width: 700px; margin: 0 auto; padding: 25px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #2563eb; }
        .header p { font-size: 11px; color: #666; margin-top: 4px; }
        .meta { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; }
        .meta table { width: 100%; }
        .meta td { padding: 3px 10px; border: none; }
        .meta .label { color: #64748b; font-size: 10px; text-transform: uppercase; }
        .meta .value { font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data thead th { background: #2563eb; color: white; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.data thead th.right { text-align: right; }
        table.data tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        table.data tbody td.right { text-align: right; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tfoot td { padding: 10px; border-top: 2px solid #2563eb; font-weight: bold; font-size: 12px; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 12px; text-align: center; color: #94a3b8; font-size: 9px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $title }}</h1>
            <p>{{ $tenantName }}</p>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td class="label">Periodo:</td>
                    <td class="value">{{ $from }} - {{ $to }}</td>
                    <td class="label">Generado:</td>
                    <td class="value">{{ now()->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
        </div>

        @if($reportType === 'sales')
            <table class="data">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th class="right">Documentos</th>
                        <th class="right">Total</th>
                        <th class="right">Impuesto</th>
                        <th class="right">Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['data'] ?? [] as $item)
                        <tr>
                            <td>{{ $item->period ?? $item['period'] ?? '' }}</td>
                            <td class="right">{{ $item->count ?? $item['count'] ?? 0 }}</td>
                            <td class="right">${{ number_format((float)($item->total ?? $item['total'] ?? 0), 2) }}</td>
                            <td class="right">${{ number_format((float)($item->tax ?? $item['tax'] ?? 0), 2) }}</td>
                            <td class="right">${{ number_format((float)($item->average ?? $item['average'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if(isset($reportData['totals']))
                    <tfoot>
                        <tr>
                            <td>TOTAL</td>
                            <td class="right">{{ $reportData['totals']['count'] ?? 0 }}</td>
                            <td class="right">${{ number_format($reportData['totals']['total'] ?? 0, 2) }}</td>
                            <td class="right">${{ number_format($reportData['totals']['tax'] ?? 0, 2) }}</td>
                            <td class="right">${{ number_format($reportData['totals']['average'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>

        @elseif($reportType === 'tax')
            <table class="data">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['subtotals'] ?? [] as $rate => $amount)
                        <tr>
                            <td>Subtotal IVA {{ $rate }}</td>
                            <td class="right">${{ number_format($amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total IVA</td>
                        <td class="right">${{ number_format($reportData['total_tax'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total General</td>
                        <td class="right">${{ number_format($reportData['total'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

        @elseif($reportType === 'customers')
            <table class="data">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Identificacion</th>
                        <th class="right">Documentos</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['data'] ?? [] as $item)
                        <tr>
                            <td>{{ $item['business_name'] ?? '' }}</td>
                            <td>{{ $item['identification'] ?? '' }}</td>
                            <td class="right">{{ $item['document_count'] ?? 0 }}</td>
                            <td class="right">${{ number_format((float)($item['total_amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($reportType === 'products')
            <table class="data">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Codigo</th>
                        <th class="right">Cantidad</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['data'] ?? [] as $item)
                        <tr>
                            <td>{{ $item['name'] ?? '' }}</td>
                            <td>{{ $item['main_code'] ?? '' }}</td>
                            <td class="right">{{ $item['quantity_sold'] ?? 0 }}</td>
                            <td class="right">${{ number_format((float)($item['total_amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($reportType === 'status')
            @php
                $statusLabels = [
                    'draft' => 'Borrador', 'processing' => 'Procesando', 'signed' => 'Firmado',
                    'sent' => 'Enviado', 'authorized' => 'Autorizado', 'rejected' => 'Rechazado',
                    'failed' => 'Fallido', 'voided' => 'Anulado',
                ];
            @endphp
            <table class="data">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th class="right">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['data'] ?? [] as $status => $count)
                        <tr>
                            <td>{{ $statusLabels[$status] ?? $status }}</td>
                            <td class="right">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($reportType === 'comparison')
            <table class="data">
                <thead>
                    <tr>
                        <th>Metrica</th>
                        <th class="right">Periodo Actual</th>
                        <th class="right">Periodo Anterior</th>
                        <th class="right">Cambio %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Documentos</td>
                        <td class="right">{{ $reportData['current']['count'] ?? 0 }}</td>
                        <td class="right">{{ $reportData['previous']['count'] ?? 0 }}</td>
                        <td class="right">{{ $reportData['changes']['count'] ?? 0 }}%</td>
                    </tr>
                    <tr>
                        <td>Total Facturado</td>
                        <td class="right">${{ number_format($reportData['current']['total'] ?? 0, 2) }}</td>
                        <td class="right">${{ number_format($reportData['previous']['total'] ?? 0, 2) }}</td>
                        <td class="right">{{ $reportData['changes']['total'] ?? 0 }}%</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <div class="footer">
            <p>Reporte generado automaticamente - AmePhia</p>
            <p>{{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
