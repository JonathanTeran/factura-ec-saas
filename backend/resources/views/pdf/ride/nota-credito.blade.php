<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de crédito {{ $documentNumber }}</title>
    @include('pdf.ride.partials.styles')
</head>
<body>
    @include('pdf.ride.partials.header', ['docTitle' => 'NOTA DE CRÉDITO'])
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
            <tr>
                <td class="k">Razón de modificación:</td>
                <td colspan="3">{{ data_get($document->additional_info, 'motivo') ?? $document->notes ?? '—' }}</td>
            </tr>
        </table>
    </div>

    @include('pdf.ride.partials.items')
    @include('pdf.ride.partials.totals')
    @include('pdf.ride.partials.footer', ['skipInfo' => true])
</body>
</html>
