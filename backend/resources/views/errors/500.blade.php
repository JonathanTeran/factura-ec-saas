@extends('errors.layout')

@section('title', 'Algo salió mal')
@section('code', '500')
@section('heading', 'Algo salió mal de nuestro lado')
@section('message')
    Ya quedó registrado y lo vamos a revisar. Tus comprobantes y datos no se han perdido. Inténtalo de nuevo en un momento.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url()->current() }}">Reintentar</a>
    <a class="btn btn-secondary" href="{{ $homeUrl }}">Ir al inicio</a>
@endsection
