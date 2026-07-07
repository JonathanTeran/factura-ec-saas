{{-- Bloque inferior (formato oficial SRI):
     izquierda = Información Adicional + Forma de pago; derecha = totales. --}}
@php
    $money = fn ($v) => number_format((float) $v, 2, '.', '');

    $pmLabels = [
        '01' => 'SIN UTILIZACIÓN DEL SISTEMA FINANCIERO',
        '15' => 'COMPENSACIÓN DE DEUDAS',
        '16' => 'TARJETA DE DÉBITO',
        '17' => 'DINERO ELECTRÓNICO',
        '18' => 'TARJETA PREPAGO',
        '19' => 'TARJETA DE CRÉDITO',
        '20' => 'OTROS CON UTILIZACIÓN DEL SISTEMA FINANCIERO',
        '21' => 'ENDOSO DE TÍTULOS',
    ];
    $payments = collect($document->payment_methods ?? []);

    // Información adicional: contacto del receptor + campos manuales.
    // Acepta mapa {clave: valor} (web) o lista [{name, value}] (app).
    $manualInfo = collect();
    foreach (($document->additional_info ?? []) as $k => $v) {
        if (is_scalar($v) && $v !== '') {
            $manualInfo[$k] = $v;
        } elseif (is_array($v) && isset($v['name']) && ($v['value'] ?? '') !== '') {
            $manualInfo[$v['name']] = $v['value'];
        }
    }
    $autoInfo = collect([
        'Dirección' => $customer?->address,
        'Teléfono' => $customer?->phone,
        'Email' => $customer?->email,
    ])->filter();
    $manualKeys = $manualInfo->keys()->map(fn ($k) => mb_strtolower($k));
    $extraInfo = $autoInfo->reject(fn ($v, $k) => $manualKeys->contains(mb_strtolower($k)))->merge($manualInfo);

    // IVA desglosado por tarifa
    $ivaByRate = collect($document->items ?? [])
        ->groupBy(fn ($it) => (string) (float) $it->tax_rate)
        ->map(fn ($group) => $group->sum('tax_value'))
        ->filter(fn ($v, $rate) => (float) $rate > 0 && $v > 0);
@endphp

<table class="summary">
    <tr>
        {{-- Izquierda: Información Adicional + Forma de pago --}}
        <td style="width: 54%;">
            @if($extraInfo->isNotEmpty())
                <div class="section-title">Información Adicional</div>
                <table class="pay" style="margin-top: 0;">
                    @foreach($extraInfo as $k => $v)
                        <tr>
                            <td class="k" style="width: 30%; background: #fafafa;">{{ $k }}</td>
                            <td>{{ $v }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if($payments->isNotEmpty())
                <table class="pay">
                    <thead>
                        <tr>
                            <th>Forma de Pago</th>
                            <th style="width: 30%;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $pm)
                            @php $code = is_array($pm) ? ($pm['code'] ?? '') : $pm; @endphp
                            <tr>
                                <td>{{ $pmLabels[$code] ?? ('CÓDIGO '.$code) }}</td>
                                <td class="right mono">{{ $money(is_array($pm) ? ($pm['amount'] ?? $document->total) : $document->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>

        {{-- Derecha: totales --}}
        <td style="width: 46%;">
            <table class="totals">
                @if((float) $document->subtotal_15 > 0)
                    <tr><td class="k">SUBTOTAL 15%</td><td class="right mono">{{ $money($document->subtotal_15) }}</td></tr>
                @endif
                @if((float) $document->subtotal_12 > 0)
                    <tr><td class="k">SUBTOTAL 12%</td><td class="right mono">{{ $money($document->subtotal_12) }}</td></tr>
                @endif
                @if((float) $document->subtotal_5 > 0)
                    <tr><td class="k">SUBTOTAL 5%</td><td class="right mono">{{ $money($document->subtotal_5) }}</td></tr>
                @endif
                <tr><td class="k">SUBTOTAL 0%</td><td class="right mono">{{ $money($document->subtotal_0) }}</td></tr>
                <tr><td class="k">SUBTOTAL NO OBJETO DE IVA</td><td class="right mono">{{ $money($document->subtotal_no_tax) }}</td></tr>
                <tr><td class="k">SUBTOTAL SIN IMPUESTOS</td><td class="right mono">{{ $money($document->subtotal) }}</td></tr>
                <tr><td class="k">TOTAL DESCUENTO</td><td class="right mono">{{ $money($document->total_discount) }}</td></tr>
                @if((float) $document->total_ice > 0)
                    <tr><td class="k">ICE</td><td class="right mono">{{ $money($document->total_ice) }}</td></tr>
                @endif
                @forelse($ivaByRate as $rate => $value)
                    <tr><td class="k">IVA {{ rtrim(rtrim(number_format((float) $rate, 2), '0'), '.') }}%</td><td class="right mono">{{ $money($value) }}</td></tr>
                @empty
                    <tr><td class="k">IVA</td><td class="right mono">{{ $money($document->total_tax) }}</td></tr>
                @endforelse
                @if((float) $document->tip > 0)
                    <tr><td class="k">PROPINA</td><td class="right mono">{{ $money($document->tip) }}</td></tr>
                @endif
                <tr class="grand"><td class="k">VALOR TOTAL</td><td class="right mono">{{ $money($document->total) }}</td></tr>
            </table>
        </td>
    </tr>
</table>
