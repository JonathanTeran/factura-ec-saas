@extends('emails.partials.layout')

@section('title', 'Tu periodo de prueba esta por finalizar')

@section('content')
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p>Facturacion Electronica Ecuador</p>
        <span class="badge {{ $daysRemaining <= 3 ? 'badge-danger' : 'badge-warning' }}">
            {{ $daysRemaining <= 1 ? 'Ultimo dia' : $daysRemaining . ' dias restantes' }}
        </span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $tenant->owner->name ?? $tenant->name }}</strong>,
    </p>

    @if($daysRemaining <= 3)
    <div class="alert-box alert-danger">
        <strong>Tu periodo de prueba termina en {{ $daysRemaining }} {{ $daysRemaining === 1 ? 'dia' : 'dias' }}.</strong>
        Si no seleccionas un plan, perderas acceso a la emision de documentos electronicos.
    </div>
    @else
    <p class="text">
        Tu periodo de prueba en {{ config('app.name') }} termina en <strong>{{ $daysRemaining }} dias</strong>.
        Para continuar emitiendo comprobantes electronicos sin interrupcion, te invitamos a elegir un plan.
    </p>
    @endif

    <table class="info-table">
        <tr>
            <td>Cuenta</td>
            <td>{{ $tenant->name }}</td>
        </tr>
        <tr>
            <td>Documentos emitidos</td>
            <td>{{ $tenant->documents_this_month ?? 0 }}</td>
        </tr>
        <tr>
            <td>Prueba finaliza</td>
            <td>{{ $tenant->trial_ends_at?->format('d/m/Y') ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="cta">
        <a href="{{ url('/panel/settings/billing') }}" class="{{ $daysRemaining <= 3 ? 'cta-danger' : 'cta-warning' }}">
            Elegir un Plan
        </a>
    </div>

    <p class="text" style="color: #6b7280; font-size: 13px;">
        Todos tus datos y documentos se mantendran seguros. Al activar un plan, retomas donde lo dejaste.
    </p>
@endsection
