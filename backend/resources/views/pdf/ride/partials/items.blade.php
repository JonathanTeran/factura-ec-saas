{{-- Tabla de detalle de ítems (columnas del formato oficial SRI) --}}
@php $money = fn ($v) => number_format((float) $v, 2, '.', ''); @endphp
<table class="items">
    <thead>
        <tr>
            <th style="width: 10%;">Cód.<br>Principal</th>
            <th style="width: 9%;">Cód.<br>Auxiliar</th>
            <th style="width: 7%;">Cant.</th>
            <th>Descripción</th>
            <th style="width: 11%;">Precio<br>Unitario</th>
            <th style="width: 9%;">Descuento</th>
            <th style="width: 11%;">Precio Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            <tr>
                <td class="mono">{{ $item->main_code }}</td>
                <td class="mono">{{ $item->aux_code }}</td>
                <td class="right mono">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                <td>{{ $item->description }}</td>
                <td class="right mono">{{ $money($item->unit_price) }}</td>
                <td class="right mono">{{ $money($item->discount) }}</td>
                <td class="right mono">{{ $money($item->subtotal) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
