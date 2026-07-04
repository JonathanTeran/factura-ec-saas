<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Guía de remisión {{ $documentNumber }}</title>
    @include('pdf.ride.partials.styles')
</head>
<body>
    @include('pdf.ride.partials.header', ['docTitle' => 'GUÍA DE REMISIÓN'])

    @php
        $info = $document->additional_info ?? [];
        $destinatarios = data_get($info, 'destinatarios', []);
        $fmtSriDate = function ($v) {
            if (!$v) return '—';
            try { return \Carbon\Carbon::createFromFormat('d/m/Y', $v)->format('d/m/Y'); }
            catch (\Throwable) { return $v; }
        };
    @endphp

    {{-- Transporte --}}
    <div class="box">
        <h3>Transporte</h3>
        <table class="kv">
            <tr>
                <td class="k" style="width: 24%;">Dirección de partida:</td>
                <td colspan="3">{{ data_get($info, 'dirPartida', '—') }}</td>
            </tr>
            <tr>
                <td class="k">Transportista:</td>
                <td style="width: 40%;">{{ data_get($info, 'razonSocialTransportista', '—') }}</td>
                <td class="k" style="width: 12%;">RUC / CI:</td>
                <td class="mono">{{ data_get($info, 'rucTransportista', '—') }}</td>
            </tr>
            <tr>
                <td class="k">Placa:</td>
                <td class="mono">{{ data_get($info, 'placa', '—') }}</td>
                <td class="k">Inicio / fin transporte:</td>
                <td>{{ $fmtSriDate(data_get($info, 'fechaIniTransporte')) }} — {{ $fmtSriDate(data_get($info, 'fechaFinTransporte')) }}</td>
            </tr>
        </table>
    </div>

    {{-- Destinatarios --}}
    @foreach($destinatarios as $dest)
        <div class="box">
            <h3>Destinatario</h3>
            <table class="kv">
                <tr>
                    <td class="k" style="width: 24%;">Razón social / Nombres:</td>
                    <td class="bold" style="width: 40%;">{{ data_get($dest, 'razonSocialDestinatario', '—') }}</td>
                    <td class="k" style="width: 14%;">Identificación:</td>
                    <td class="mono">{{ data_get($dest, 'identificacionDestinatario', '—') }}</td>
                </tr>
                <tr>
                    <td class="k">Dirección destino:</td>
                    <td>{{ data_get($dest, 'dirDestinatario', '—') }}</td>
                    <td class="k">Motivo traslado:</td>
                    <td>{{ data_get($dest, 'motivoTraslado', '—') }}</td>
                </tr>
                @if(data_get($dest, 'numDocSustento'))
                    <tr>
                        <td class="k">Doc. sustento:</td>
                        <td class="mono">{{ data_get($dest, 'codDocSustento') === '01' ? 'FACTURA' : data_get($dest, 'codDocSustento') }} {{ data_get($dest, 'numDocSustento') }}</td>
                        <td class="k">Fecha sustento:</td>
                        <td>{{ $fmtSriDate(data_get($dest, 'fechaEmisionDocSustento')) }}</td>
                    </tr>
                @endif
            </table>

            <table class="items" style="margin-top: 5px; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 16%;">Código</th>
                        <th style="width: 10%;">Cantidad</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(data_get($dest, 'detalles', []) as $det)
                        <tr>
                            <td class="mono">{{ data_get($det, 'codigoInterno', '') }}</td>
                            <td class="right mono">{{ data_get($det, 'cantidad', '') }}</td>
                            <td>{{ data_get($det, 'descripcion', '') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @include('pdf.ride.partials.footer', ['skipInfo' => true])
</body>
</html>
