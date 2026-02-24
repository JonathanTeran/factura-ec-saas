@extends('emails.partials.layout')

@section('title', 'Bienvenido a ' . config('app.name'))

@section('content')
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p>Facturacion Electronica Ecuador</p>
        <span class="badge badge-success">Cuenta Creada</span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $user->name }}</strong>,
    </p>
    <p class="text">
        Bienvenido/a a {{ config('app.name') }}. Tu cuenta ha sido creada exitosamente
        y estas listo/a para comenzar a emitir comprobantes electronicos autorizados por el SRI.
    </p>

    @if(!empty($temporaryPassword))
    <div class="alert-box alert-warning">
        <strong>Tu contrasena temporal:</strong> {{ $temporaryPassword }}<br>
        <small>Por seguridad, cambiala en tu primer inicio de sesion.</small>
    </div>
    @endif

    <div class="alert-box alert-info">
        <strong>Para comenzar a facturar necesitas:</strong>
        <ol style="margin: 8px 0 0 0; padding-left: 20px; font-size: 14px;">
            <li>Completar los datos de tu empresa (RUC, razon social)</li>
            <li>Subir tu firma electronica (.p12)</li>
            <li>Configurar tu sucursal y punto de emision</li>
            <li>Crear tu primer cliente y emitir tu primera factura</li>
        </ol>
    </div>

    <div class="cta">
        <a href="{{ url('/panel/onboarding') }}" class="cta-success">Comenzar Configuracion</a>
    </div>

    <table class="info-table">
        <tr>
            <td>Email de acceso</td>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <td>Empresa</td>
            <td>{{ $tenant->name }}</td>
        </tr>
        <tr>
            <td>Plan</td>
            <td>{{ $tenant->plan->name ?? 'Periodo de prueba' }}</td>
        </tr>
    </table>
@endsection

@section('footer')
    <p>Si no creaste esta cuenta, puedes ignorar este correo.</p>
@endsection
