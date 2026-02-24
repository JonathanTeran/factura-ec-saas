@extends('emails.partials.layout')

@section('title', 'Documento Rechazado')

@section('content')
    <div class="header">
        <h2>{{ $company->business_name }}</h2>
        <p>RUC: {{ $company->ruc }}</p>
        <span class="badge badge-danger">Documento Rechazado</span>
    </div>

    <p class="greeting">
        Estimado/a <strong>{{ $user->name }}</strong>,
    </p>
    <p class="text">
        Le informamos que su {{ strtolower($document->document_type->label()) }} <strong>{{ $document->getDocumentNumber() }}</strong>
        ha sido <strong>rechazado</strong> por el Servicio de Rentas Internas (SRI).
    </p>

    <table class="info-table">
        <tr>
            <td>Tipo de documento</td>
            <td>{{ $document->document_type->label() }}</td>
        </tr>
        <tr>
            <td>Numero</td>
            <td>{{ $document->getDocumentNumber() }}</td>
        </tr>
        <tr>
            <td>Cliente</td>
            <td>{{ $document->customer->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>${{ number_format($document->total, 2) }}</td>
        </tr>
    </table>

    @if(!empty($errors))
    <div class="alert-box alert-danger">
        <strong>Errores del SRI:</strong>
        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
            @foreach((array) $errors as $error)
                <li>{{ is_string($error) ? $error : json_encode($error) }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="cta">
        <a href="{{ url('/panel/documents/' . $document->id) }}" class="cta-danger">Revisar Documento</a>
    </div>

    <p class="text" style="color: #6b7280; font-size: 13px;">
        Puede corregir los errores y reenviar el documento al SRI desde el panel de administracion.
    </p>
@endsection

@section('footer')
    <p>{{ $company->business_name }} - {{ $company->address ?? '' }}</p>
@endsection
