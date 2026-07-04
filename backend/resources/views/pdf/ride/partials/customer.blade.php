{{-- Bloque comprador/receptor a lo ancho (formato oficial SRI).
     $showTransport: muestra fila Placa/Matrícula + Guía (facturas). --}}
<div class="box" style="margin-top: 9px;">
    <table class="kv">
        <tr>
            <td class="k" style="width: 20%;">Razón Social / Nombres y Apellidos:</td>
            <td class="bold">{{ $customer?->name ?? 'CONSUMIDOR FINAL' }}</td>
        </tr>
        <tr>
            <td class="k">Identificación:</td>
            <td class="mono">{{ $customer?->identification ?? '9999999999999' }}</td>
        </tr>
        <tr>
            <td class="k">Fecha de Emisión:</td>
            <td>{{ $document->issue_date?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="k">Dirección:</td>
            <td>{{ $customer?->address ?: '—' }}</td>
        </tr>
    </table>
</div>
