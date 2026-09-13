@extends('emails.partials.layout')

@section('title', 'Tu período de prueba está por terminar')

@section('content')
    <div class="header">
        <h2>Tu período de prueba termina pronto</h2>
        <p>Elige un plan para seguir facturando sin interrupciones.</p>
        <span class="badge {{ $daysRemaining <= 3 ? 'badge-danger' : 'badge-warning' }}">
            {{ $daysRemaining <= 1 ? 'Último día' : $daysRemaining . ' días restantes' }}
        </span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $tenant->owner->name ?? $tenant->name }}</strong>,
    </p>

    @if($daysRemaining <= 3)
    <div class="alert-box alert-danger">
        <strong>Tu período de prueba termina en {{ $daysRemaining }} {{ $daysRemaining === 1 ? 'día' : 'días' }}.</strong>
        Si no seleccionas un plan, perderás acceso a la emisión de documentos electrónicos.
    </div>
    @else
    <p class="text">
        Tu período de prueba en Facturón termina en <strong>{{ $daysRemaining }} días</strong>.
        Para continuar emitiendo comprobantes electrónicos sin interrupción, elige el plan que mejor se ajuste a tu negocio.
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
            <td>La prueba termina</td>
            <td>{{ $tenant->trial_ends_at?->format('d/m/Y') ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="cta">
        <a href="{{ url('/settings/subscription') }}" class="{{ $daysRemaining <= 3 ? 'cta-danger' : 'cta-warning' }}">
            Elegir un plan
        </a>
    </div>

    <p class="text" style="color: #64748b; font-size: 13px;">
        Todos tus datos y documentos se mantendrán seguros. Al activar un plan, retomas donde lo dejaste.
    </p>
@endsection
