@extends('emails.partials.layout')

@section('title', 'Pago recibido')

@section('content')
    <div class="header">
        <h2>Pago recibido</h2>
        <p>Gracias por tu pago. Este es el detalle de la transacción.</p>
        <span class="badge badge-success">Pago confirmado</span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $payment->billing_name ?? $payment->tenant->name }}</strong>,
    </p>
    <p class="text">
        Hemos recibido tu pago correctamente. A continuación los detalles:
    </p>

    <table class="info-table">
        <tr>
            <td>No. de transacción</td>
            <td>{{ $payment->transaction_id }}</td>
        </tr>
        <tr>
            <td>Descripción</td>
            <td>{{ $payment->description ?? 'Suscripción ' . ($payment->subscription?->plan?->name ?? '') }}</td>
        </tr>
        <tr>
            <td>Método de pago</td>
            <td>{{ $payment->payment_method->label() }}</td>
        </tr>
        <tr>
            <td>Subtotal</td>
            <td>${{ number_format($payment->amount, 2) }}</td>
        </tr>
        @if($payment->tax_amount > 0)
        <tr>
            <td>IVA (15%)</td>
            <td>${{ number_format($payment->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>Total</strong></td>
            <td><strong>${{ number_format($payment->total_amount, 2) }} {{ $payment->currency }}</strong></td>
        </tr>
        <tr>
            <td>Fecha de pago</td>
            <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    @if($payment->subscription?->ends_at)
    <div class="alert-box alert-info">
        Tu suscripción está activa hasta el <strong>{{ $payment->subscription->ends_at->format('d/m/Y') }}</strong>.
    </div>
    @endif

    <div class="cta">
        <a href="{{ url('/settings/subscription') }}" class="cta-primary">Ver mi suscripción</a>
    </div>
@endsection

@section('footer')
    <p>Conserva este correo como comprobante de pago.</p>
@endsection
