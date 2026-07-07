{{-- Información adicional (formato oficial): solo para documentos que no la
     muestran ya en el bloque de totales (retención, guía). Facturas la
     incluyen dentro de totals.blade con $skipInfo = true. --}}
@php
    $showInfo = empty($skipInfo);
    if ($showInfo) {
        // Acepta dos formatos: mapa {clave: valor} (web) o lista
        // [{name, value}] (app). Normaliza a {clave: valor}.
        $manualInfo = collect();
        foreach (($document->additional_info ?? []) as $k => $v) {
            if (is_scalar($v) && $v !== '') {
                $manualInfo[$k] = $v;
            } elseif (is_array($v) && isset($v['name']) && ($v['value'] ?? '') !== '') {
                $manualInfo[$v['name']] = $v['value'];
            }
        }
        $autoInfo = collect([
            'Dirección' => $customer?->address ?? null,
            'Teléfono' => $customer?->phone ?? null,
            'Email' => $customer?->email ?? null,
        ])->filter();
        $manualKeys = $manualInfo->keys()->map(fn ($k) => mb_strtolower($k));
        $extraInfo = $autoInfo->reject(fn ($v, $k) => $manualKeys->contains(mb_strtolower($k)))->merge($manualInfo);
    } else {
        $extraInfo = collect();
    }
    $rideFooter = data_get($company->settings, 'documents.ride_footer');
@endphp

@if($extraInfo->isNotEmpty())
    <div style="margin-top: 8px;">
        <div class="section-title">Información Adicional</div>
        <table class="pay" style="margin-top: 0;">
            @foreach($extraInfo as $k => $v)
                <tr>
                    <td class="k" style="width: 22%; background: #fafafa;">{{ $k }}</td>
                    <td>{{ $v }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@if($rideFooter)
    <div class="footer-note">{{ $rideFooter }}</div>
@endif
<div class="footer-note">
    Documento generado por AmePhia Facturación · www.amephia.com
</div>
