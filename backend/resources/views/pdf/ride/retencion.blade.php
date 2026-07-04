<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Retención {{ $documentNumber }}</title>
    @include('pdf.ride.partials.styles')
</head>
<body>
    @include('pdf.ride.partials.header', ['docTitle' => 'COMPROBANTE DE RETENCIÓN'])
    @include('pdf.ride.partials.customer', ['partyTitle' => 'Datos del sujeto retenido'])

    @php
        $money = fn ($v) => number_format((float) $v, 2, '.', '');
        $details = $document->withholdingDetails ?? collect();
        $docTypeLabels = ['01' => 'FACTURA', '03' => 'LIQUIDACIÓN DE COMPRA'];
        $fiscalPeriod = $document->issue_date?->format('m/Y');
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th style="width: 14%;">Comprobante</th>
                <th style="width: 15%;">Número</th>
                <th style="width: 10%;">Fecha emisión</th>
                <th style="width: 10%;">Ejercicio fiscal</th>
                <th style="width: 12%;">Base imponible</th>
                <th style="width: 9%;">Impuesto</th>
                <th style="width: 10%;">Código</th>
                <th style="width: 9%;">% retención</th>
                <th style="width: 11%;">Valor retenido</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $d)
                <tr>
                    <td>{{ $docTypeLabels[$d->support_doc_code] ?? $d->support_doc_code }}</td>
                    <td class="mono">{{ $d->support_doc_number }}</td>
                    <td class="center mono">{{ $d->support_doc_date?->format('d/m/Y') }}</td>
                    <td class="center mono">{{ $fiscalPeriod }}</td>
                    <td class="right mono">{{ $money($d->tax_base) }}</td>
                    <td class="center">{{ strtoupper($d->tax_type ?? '') === 'IVA' || $d->tax_type === 'iva' ? 'IVA' : 'RENTA' }}</td>
                    <td class="center mono">{{ $d->retention_code }}</td>
                    <td class="right mono">{{ rtrim(rtrim(number_format((float) $d->retention_rate, 2), '0'), '.') }}</td>
                    <td class="right mono">{{ $money($d->retained_value) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="right bold" style="border: 1px solid #334155; background: #334155; color: #fff; padding: 4px 5px;">TOTAL RETENIDO</td>
                <td class="right mono bold" style="border: 1px solid #334155; background: #334155; color: #fff; padding: 4px 5px;">{{ $money($details->sum('retained_value')) }}</td>
            </tr>
        </tfoot>
    </table>

    @include('pdf.ride.partials.footer')
</body>
</html>
