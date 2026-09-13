@extends('emails.partials.layout')

@section('title', 'Cotización '.$quote->quote_number)

@section('content')
    @php
        $issuer = $company?->trade_name ?: $company?->business_name;
        $validUntil = $quote->expiry_date?->format('d/m/Y');
    @endphp
    <div class="header">
        <h2>Cotización {{ $quote->quote_number }}</h2>
        <p>{{ $issuer ? $issuer.' te envía una propuesta comercial.' : 'Te enviamos una propuesta comercial.' }}</p>
        <span class="badge badge-info">Proforma adjunta en PDF</span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $customer?->name ?? 'estimado cliente' }}</strong>,
    </p>

    @if(filled($customMessage))
        <p class="text">{!! nl2br(e($customMessage)) !!}</p>
    @else
        <p class="text">
            Adjuntamos la cotización <strong>{{ $quote->quote_number }}</strong>
            @if($validUntil) válida hasta el <strong>{{ $validUntil }}</strong>@endif.
            Si tienes dudas o deseas ajustar algún detalle, responde a este correo.
        </p>
    @endif

    <table class="info-table" role="presentation">
        <tr><td>Número</td><td>{{ $quote->quote_number }}</td></tr>
        <tr><td>Fecha de emisión</td><td>{{ $quote->issue_date?->format('d/m/Y') }}</td></tr>
        @if($validUntil)
            <tr><td>Válida hasta</td><td>{{ $validUntil }}</td></tr>
        @endif
        <tr><td>Subtotal</td><td>${{ number_format((float) $quote->subtotal, 2) }}</td></tr>
        <tr><td>IVA</td><td>${{ number_format((float) $quote->total_tax, 2) }}</td></tr>
        <tr><td>Total</td><td>${{ number_format((float) $quote->total, 2) }}</td></tr>
        @if($quote->payment_terms)
            <tr><td>Condiciones de pago</td><td>{{ $quote->payment_terms }}</td></tr>
        @endif
    </table>

    <div class="alert-box alert-info">
        Esta cotización es una propuesta comercial y no tiene validez tributaria. Al aceptarla,
        {{ $issuer ?: 'el emisor' }} emitirá la factura electrónica autorizada por el SRI.
    </div>
@endsection

@section('footer')
    @if($company?->email || $company?->phone)
        <p>Contacto de {{ $issuer }}: {{ collect([$company?->email, $company?->phone])->filter()->implode(' · ') }}</p>
    @endif
@endsection
