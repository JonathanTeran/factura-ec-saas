@extends('errors.layout')

@section('title', 'Página no encontrada')
@section('code', '404')
@section('heading', 'No encontramos esta página')
@section('message')
    La dirección puede estar mal escrita o la página ya no existe. Revisa el enlace o vuelve al inicio.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ $homeUrl }}">Ir al inicio</a>
    <a class="btn btn-secondary" href="{{ $panelUrl }}">Ir a mi panel</a>
@endsection
