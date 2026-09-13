@extends('errors.layout')

@push('head')
    <meta http-equiv="refresh" content="30">
@endpush

@section('title', 'Mantenimiento')
@section('code', '503')
@section('heading', 'Estamos actualizando Facturón')
@section('message')
    Volvemos en unos minutos. Esta página se recargará sola; tus comprobantes y datos están a salvo.
@endsection
@section('hint')
    Si estabas emitiendo un comprobante, al volver revisa su estado en <strong>Documentos</strong>: la autorización del SRI continúa en segundo plano.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url()->current() }}">Reintentar ahora</a>
@endsection
