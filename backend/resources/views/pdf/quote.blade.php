<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quote->quote_number }}</title>
    @include('pdf.ride.partials.styles')
    <style>
        body { font-size: 8.6px; }
        .brand-bar { height: 4px; width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .quote-title { font-size: 17px; font-weight: bold; letter-spacing: 0.6px; color: #0b1220; }
        .quote-number { font-family: Courier, monospace; font-size: 12px; font-weight: bold; }
        .status-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 7.4px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; background: #eef2ff; color: #2b54e4; }
        .status-expired { background: #fff7ed; color: #b45309; }
        .status-accepted, .status-invoiced { background: #ecfdf5; color: #047857; }
        .status-rejected { background: #fef2f2; color: #b91c1c; }
        .terms { margin-top: 10px; }
        .terms .box { min-height: 40px; }
        .disclaimer { margin-top: 14px; padding: 7px 9px; border: 0.9px dashed #999; border-radius: 3px; font-size: 7.4px; color: #444; }
    </style>
</head>
<body>
@php
    $money = fn ($v) => number_format((float) $v, 2, '.', '');
    $logoFile = $company?->logo_path ? storage_path('app/public/'.$company->logo_path) : null;
    $hasLogo = $logoFile && is_file($logoFile);
    $issuerName = $company?->trade_name ?: $company?->business_name;
    $initials = collect(preg_split('/\s+/', mb_strtoupper((string) $issuerName)))
        ->filter(fn ($w) => mb_strlen($w) > 2 || is_numeric($w))
        ->take(2)
        ->map(fn ($w) => mb_substr($w, 0, 1))
        ->implode('');
    $status = $quote->status;
    $statusValue = is_object($status) ? $status->value : (string) $status;
    $statusLabel = is_object($status) ? $status->label() : (string) $status;
    $ivaByRate = $items->groupBy(fn ($it) => (string) (float) $it->tax_rate)
        ->map(fn ($group) => $group->sum('tax_value'))
        ->filter(fn ($v, $rate) => (float) $rate > 0 && $v > 0);
    $validUntil = $quote->expiry_date?->format('d/m/Y');
@endphp

<table class="brand-bar" role="presentation">
    <tr>
        <td style="width: 34%; background: #FFCE00; height: 4px; padding: 0;"></td>
        <td style="width: 33%; background: #0653C6; height: 4px; padding: 0;"></td>
        <td style="width: 33%; background: #EF3340; height: 4px; padding: 0;"></td>
    </tr>
</table>

<table class="w-100" style="border-collapse: separate; border-spacing: 9px 0; margin: 0 -9px;">
    <tr>
        <td style="width: 52%; vertical-align: top;">
            <div class="box" style="min-height: 150px;">
                <table class="w-100" style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 70px; vertical-align: top; padding-right: 8px;">
                            @if($hasLogo)
                                <img src="{{ $logoFile }}" style="max-height: 56px; max-width: 64px;" alt="logo">
                            @else
                                <div style="width: 52px; height: 52px; background: #0b1220; color: #fff; border-radius: 8px; font-size: 20px; font-weight: bold; line-height: 52px; text-align: center;">{{ $initials ?: 'EC' }}</div>
                            @endif
                        </td>
                        <td style="vertical-align: top;">
                            <div class="bold" style="font-size: 10.5px;">{{ $company?->business_name }}</div>
                            @if($company?->trade_name && $company->trade_name !== $company->business_name)
                                <div class="muted">{{ $company->trade_name }}</div>
                            @endif
                            <table class="kv" style="margin-top: 5px;">
                                <tr><td class="k" style="width: 24%;">RUC:</td><td class="mono bold">{{ $company?->ruc }}</td></tr>
                                <tr><td class="k">Dirección:</td><td>{{ $company?->address ?: '—' }}</td></tr>
                                @if($company?->phone)
                                    <tr><td class="k">Teléfono:</td><td>{{ $company->phone }}</td></tr>
                                @endif
                                @if($company?->email)
                                    <tr><td class="k">Correo:</td><td>{{ $company->email }}</td></tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td style="width: 48%; vertical-align: top;">
            <div class="box" style="min-height: 150px;">
                <div class="quote-title">COTIZACIÓN</div>
                <div class="muted" style="margin-bottom: 6px;">Proforma · propuesta comercial</div>
                <table class="kv">
                    <tr><td class="k" style="width: 38%;">No.</td><td class="quote-number">{{ $quote->quote_number }}</td></tr>
                    <tr><td class="k">Fecha de emisión:</td><td class="bold">{{ $quote->issue_date?->format('d/m/Y') }}</td></tr>
                    <tr><td class="k">Válida hasta:</td><td class="bold">{{ $validUntil ?: 'Sin fecha de vencimiento' }}</td></tr>
                    <tr><td class="k">Estado:</td><td><span class="status-pill status-{{ $statusValue }}">{{ $statusLabel }}</span></td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="box" style="margin-top: 9px;">
    <table class="kv">
        <tr>
            <td class="k" style="width: 20%;">Cliente:</td>
            <td class="bold">{{ $customer?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Identificación:</td>
            <td class="mono">{{ $customer?->identification ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Dirección:</td>
            <td>{{ $customer?->address ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Contacto:</td>
            <td>{{ collect([$customer?->email, $customer?->phone])->filter()->implode(' · ') ?: '—' }}</td>
        </tr>
    </table>
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width: 6%;">#</th>
            <th style="width: 9%;">Cant.</th>
            <th>Descripción</th>
            <th style="width: 12%;">Precio unit.</th>
            <th style="width: 10%;">Descuento</th>
            <th style="width: 8%;">IVA</th>
            <th style="width: 13%;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $index => $item)
            <tr>
                <td class="center mono">{{ $index + 1 }}</td>
                <td class="right mono">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                <td>
                    {{ $item->description }}
                    @if($item->product?->main_code)
                        <div class="muted mono" style="font-size: 7px;">{{ $item->product->main_code }}</div>
                    @endif
                </td>
                <td class="right mono">{{ $money($item->unit_price) }}</td>
                <td class="right mono">{{ $money($item->discount) }}</td>
                <td class="right mono">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2), '0'), '.') }}%</td>
                <td class="right mono">{{ $money($item->subtotal) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="center muted">Sin ítems</td></tr>
        @endforelse
    </tbody>
</table>

<table class="summary">
    <tr>
        <td style="width: 54%;">
            @if($quote->payment_terms || $quote->notes)
                <div class="section-title">Condiciones</div>
                <table class="pay" style="margin-top: 0;">
                    @if($quote->payment_terms)
                        <tr>
                            <td class="k" style="width: 30%; background: #fafafa;">Forma de pago</td>
                            <td>{{ $quote->payment_terms }}</td>
                        </tr>
                    @endif
                    @if($quote->notes)
                        <tr>
                            <td class="k" style="width: 30%; background: #fafafa;">Notas</td>
                            <td>{{ $quote->notes }}</td>
                        </tr>
                    @endif
                </table>
            @endif
        </td>
        <td style="width: 46%;">
            <table class="totals">
                <tr><td class="k">SUBTOTAL SIN IMPUESTOS</td><td class="right mono">{{ $money($quote->subtotal) }}</td></tr>
                <tr><td class="k">TOTAL DESCUENTO</td><td class="right mono">{{ $money($quote->total_discount) }}</td></tr>
                @forelse($ivaByRate as $rate => $value)
                    <tr><td class="k">IVA {{ rtrim(rtrim(number_format((float) $rate, 2), '0'), '.') }}%</td><td class="right mono">{{ $money($value) }}</td></tr>
                @empty
                    <tr><td class="k">IVA</td><td class="right mono">{{ $money($quote->total_tax) }}</td></tr>
                @endforelse
                <tr class="grand"><td class="k">VALOR TOTAL</td><td class="right mono">{{ $money($quote->total) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="disclaimer">
    Esta cotización es una propuesta comercial y no tiene validez tributaria. Los precios incluyen los impuestos
    indicados y son válidos {{ $validUntil ? 'hasta el '.$validUntil : 'salvo cambio sin previo aviso' }}.
    Al aceptarla se emitirá la factura electrónica correspondiente autorizada por el SRI.
</div>

<div class="footer-note">
    Documento generado por Facturón EC · facturon.ec
</div>
</body>
</html>
