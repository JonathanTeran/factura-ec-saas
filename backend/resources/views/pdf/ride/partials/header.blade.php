{{-- Cabecera RIDE (formato oficial SRI): columna izquierda = logo + emisor,
     columna derecha = datos del comprobante y autorización.
     Requiere: $document, $company, $branch, $documentNumber, $accessKey,
     $authorizationNumber, $authorizationDate, $barcode, $preview, $docTitle --}}
@if(!empty($preview))
    <div class="watermark">BORRADOR · SIN VALIDEZ TRIBUTARIA</div>
@endif

@php
    $logoFile = $company->logo_path ? storage_path('app/public/'.$company->logo_path) : null;
    $hasLogo = $logoFile && is_file($logoFile);
    $initials = collect(preg_split('/\s+/', mb_strtoupper($company->trade_name ?: $company->business_name)))
        ->filter(fn ($w) => mb_strlen($w) > 2 || is_numeric($w))
        ->take(2)
        ->map(fn ($w) => mb_substr($w, 0, 1))
        ->implode('');
    $matriz = $company->address;
    $sucursal = $branch?->address ?: $matriz;
@endphp

<table class="w-100" style="border-collapse: separate; border-spacing: 9px 0; margin: 0 -9px;">
    <tr>
        {{-- Columna izquierda: logo + emisor --}}
        <td style="width: 50%; vertical-align: top;">
            <div class="box center" style="height: 92px; padding: 10px;">
                <table class="w-100" style="height: 72px; border-collapse: collapse;">
                    <tr><td class="center" style="vertical-align: middle;">
                        @if($hasLogo)
                            <img src="{{ $logoFile }}" style="max-height: 64px; max-width: 180px;" alt="logo">
                        @else
                            <div style="display: inline-block; width: 58px; height: 58px; background: #222; color: #fff; border-radius: 8px; font-size: 24px; font-weight: bold; line-height: 58px;">{{ $initials ?: 'EC' }}</div>
                        @endif
                    </td></tr>
                </table>
            </div>

            <div class="box" style="margin-top: 9px; min-height: 132px;">
                <div class="bold" style="font-size: 9.4px;">{{ $company->business_name }}</div>
                @if($company->trade_name && $company->trade_name !== $company->business_name)
                    <div style="margin-bottom: 5px;">{{ $company->trade_name }}</div>
                @else
                    <div style="margin-bottom: 5px;">&nbsp;</div>
                @endif
                <table class="kv">
                    <tr><td class="k" style="width: 34%;">Dirección Matriz:</td><td>{{ $matriz }}</td></tr>
                    <tr><td class="k">Dirección Sucursal:</td><td>{{ $sucursal }}</td></tr>
                    @if($company->special_taxpayer && $company->special_taxpayer_number)
                        <tr><td class="k">Contribuyente Especial Nro:</td><td>{{ $company->special_taxpayer_number }}</td></tr>
                    @endif
                    <tr><td class="k">Obligado a llevar contabilidad:</td><td class="bold">{{ $company->obligated_accounting ? 'SÍ' : 'NO' }}</td></tr>
                    @if($company->retention_agent_number)
                        <tr><td class="k">Agente de retención Nro:</td><td>{{ $company->retention_agent_number }}</td></tr>
                    @endif
                    @if($company->rimpe_type === 'emprendedor')
                        <tr><td class="k">Régimen:</td><td class="bold">CONTRIBUYENTE RÉGIMEN RIMPE</td></tr>
                    @elseif($company->rimpe_type === 'negocio_popular')
                        <tr><td class="k">Régimen:</td><td class="bold">NEGOCIO POPULAR - RÉGIMEN RIMPE</td></tr>
                    @endif
                </table>
            </div>
        </td>

        {{-- Columna derecha: datos del comprobante --}}
        <td style="width: 50%; vertical-align: top;">
            <div class="box" style="min-height: 233px;">
                <table class="kv" style="margin-bottom: 6px;">
                    <tr>
                        <td class="k bold" style="width: 26%; font-size: 9px; vertical-align: middle;">R.U.C.:</td>
                        <td class="bold mono" style="font-size: 10px; vertical-align: middle;">{{ $company->ruc }}</td>
                    </tr>
                </table>

                <div class="doc-title">{{ $docTitle }}</div>

                <table class="kv" style="margin-top: 6px;">
                    <tr><td class="k" style="width: 26%;">No.</td><td class="bold mono doc-number">{{ $documentNumber }}</td></tr>
                </table>

                <div class="label" style="margin-top: 8px;">Número de Autorización</div>
                <div class="access-key">{{ $authorizationNumber ?: ($accessKey ?: 'PENDIENTE DE AUTORIZACIÓN') }}</div>

                <table class="kv" style="margin-top: 7px;">
                    <tr>
                        <td class="k" style="width: 40%;">Fecha y Hora de Autorización:</td>
                        <td>{{ $authorizationDate ? $authorizationDate->format('d/m/Y H:i:s') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Ambiente:</td>
                        <td class="bold">{{ $document->environment === '2' ? 'PRODUCCIÓN' : 'PRUEBAS' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Emisión:</td>
                        <td class="bold">NORMAL</td>
                    </tr>
                </table>

                <div class="label" style="margin-top: 8px;">Clave de Acceso</div>
                @if($barcode)
                    <div class="center" style="margin-top: 3px;">
                        <img src="{{ $barcode }}" style="width: 100%; max-width: 320px; height: 36px;" alt="Código de barras clave de acceso">
                    </div>
                    <div class="access-key center" style="margin-top: 2px;">{{ $accessKey }}</div>
                @else
                    <div class="access-key">{{ $accessKey ?: 'Se generará al enviar el documento al SRI' }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>
