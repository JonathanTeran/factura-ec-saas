@extends('emails.partials.layout')

@section('title', 'Suscripción activada')

@section('content')
    <div class="header">
        <h2>Suscripción activada</h2>
        <p>Plan {{ $subscription->plan->name }} · gracias por confiar en Facturón.</p>
        <span class="badge badge-success">Suscripción activa</span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $subscription->tenant->owner->name ?? $subscription->tenant->name }}</strong>,
    </p>
    <p class="text">
        Tu suscripción al plan <strong>{{ $subscription->plan->name }}</strong> quedó activada. Este es el resumen:
    </p>

    <table class="info-table">
        <tr>
            <td>Plan</td>
            <td>{{ $subscription->plan->name }}</td>
        </tr>
        <tr>
            <td>Ciclo de facturación</td>
            <td>{{ $subscription->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</td>
        </tr>
        <tr>
            <td>Precio</td>
            <td>${{ number_format($subscription->final_price, 2) }} USD</td>
        </tr>
        @if($subscription->discount_amount > 0)
        <tr>
            <td>Descuento aplicado</td>
            <td>-${{ number_format($subscription->discount_amount, 2) }} USD</td>
        </tr>
        @endif
        <tr>
            <td>Inicio</td>
            <td>{{ $subscription->starts_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Próximo cobro</td>
            <td>{{ $subscription->ends_at?->format('d/m/Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Documentos por mes</td>
            <td>{{ $subscription->plan->max_documents_per_month == -1 ? 'Ilimitados' : $subscription->plan->max_documents_per_month }}</td>
        </tr>
        <tr>
            <td>Usuarios</td>
            <td>{{ $subscription->plan->max_users == -1 ? 'Ilimitados' : $subscription->plan->max_users }}</td>
        </tr>
    </table>

    <div class="cta">
        <a href="{{ url('/dashboard') }}" class="cta-primary">Ir a mi panel</a>
    </div>
@endsection

@section('footer')
    <p>Puedes gestionar tu suscripción desde Configuración → Suscripción.</p>
@endsection
