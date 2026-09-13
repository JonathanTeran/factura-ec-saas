@extends('emails.partials.layout')

@section('title', 'Documento rechazado por el SRI')

@section('content')
    <div class="header">
        <h2>Documento rechazado por el SRI</h2>
        <p>{{ $company->business_name }} · RUC {{ $company->ruc }}</p>
        <span class="badge badge-danger">Requiere corrección</span>
    </div>

    <p class="greeting">
        Hola <strong>{{ $user->name }}</strong>,
    </p>
    <p class="text">
        El Servicio de Rentas Internas (SRI) <strong>rechazó</strong> tu {{ strtolower($document->document_type->label()) }}
        <strong>{{ $document->getDocumentNumber() }}</strong>. Revisa los motivos, corrige y vuelve a enviarlo.
    </p>

    <table class="info-table">
        <tr>
            <td>Tipo de documento</td>
            <td>{{ $document->document_type->label() }}</td>
        </tr>
        <tr>
            <td>Número</td>
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
        <strong>Errores reportados por el SRI:</strong>
        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
            @foreach((array) $errors as $error)
                <li>{{ is_string($error) ? $error : json_encode($error) }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="cta">
        <a href="{{ url('/documents/' . $document->id) }}" class="cta-danger">Revisar documento</a>
    </div>

    <p class="text" style="color: #64748b; font-size: 13px;">
        Puedes corregir los errores y reenviar el documento al SRI desde tu panel.
    </p>
@endsection

@section('footer')
    <p>{{ $company->business_name }}{{ $company->address ? ' · ' . $company->address : '' }}</p>
@endsection
