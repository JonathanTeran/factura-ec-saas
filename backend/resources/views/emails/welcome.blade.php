@extends('emails.partials.layout')

@section('title', 'Bienvenido a Facturón')

@section('content')
    <div class="header">
        <h2>¡Bienvenido a Facturón!</h2>
        <p>Tu cuenta está lista para emitir comprobantes autorizados por el SRI.</p>
        <span class="badge badge-success">Cuenta creada</span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $user->name }}</strong>,
    </p>
    <p class="text">
        Gracias por unirte. En unos minutos puedes estar emitiendo facturas, retenciones y guías
        firmadas con tu certificado y autorizadas por el SRI.
    </p>

    @if(!empty($temporaryPassword))
    <div class="alert-box alert-warning">
        <strong>Tu contraseña temporal:</strong> {{ $temporaryPassword }}<br>
        <small>Por seguridad, cámbiala en tu primer inicio de sesión.</small>
    </div>
    @endif

    <div class="alert-box alert-info">
        <strong>Para comenzar a facturar necesitas:</strong>
        <ol style="margin: 8px 0 0 0; padding-left: 20px; font-size: 14px;">
            <li>Completar los datos de tu empresa (RUC, razón social)</li>
            <li>Subir tu firma electrónica (.p12)</li>
            <li>Configurar tu establecimiento y punto de emisión</li>
            <li>Crear tu primer cliente y emitir tu primera factura</li>
        </ol>
    </div>

    <div class="cta">
        <a href="{{ url('/onboarding') }}" class="cta-primary">Comenzar configuración</a>
    </div>

    <table class="info-table">
        <tr>
            <td>Correo de acceso</td>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <td>Empresa</td>
            <td>{{ $tenant->name }}</td>
        </tr>
        <tr>
            <td>Plan</td>
            <td>{{ $tenant->plan->name ?? 'Por elegir' }}</td>
        </tr>
    </table>
@endsection

@section('footer')
    <p>Si no creaste esta cuenta, puedes ignorar este correo.</p>
@endsection
