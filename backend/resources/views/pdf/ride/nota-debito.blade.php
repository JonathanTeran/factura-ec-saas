<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de débito {{ $documentNumber }}</title>
    @include('pdf.ride.partials.styles')
</head>
<body>
    @include('pdf.ride.partials.header', ['docTitle' => 'NOTA DE DÉBITO'])
    @include('pdf.ride.partials.customer')

    {{-- Comprobante que se modifica --}}
    <div class="box">
        <table class="kv">
            <tr>
                <td class="k" style="width: 24%;">Comprobante que se modifica:</td>
                <td class="mono" style="width: 30%;">
                    {{ $document->related_document_type === '01' ? 'FACTURA' : ($document->related_document_type ?? 'FACTURA') }}
                    {{ $document->related_document_number ?? '—' }}
                </td>
                <td class="k" style="width: 20%;">Fecha emisión (comprobante):</td>
                <td>{{ $document->related_document_date?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Motivos --}}
    @php $money = fn ($v) => number_format((float) $v, 2, '.', ''); @endphp
    <table class="items">
        <thead>
            <tr>
                <th>Razón de la modificación</th>
                <th style="width: 15%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right mono">{{ $money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.ride.partials.totals')
    @include('pdf.ride.partials.footer', ['skipInfo' => true])
</body>
</html>
