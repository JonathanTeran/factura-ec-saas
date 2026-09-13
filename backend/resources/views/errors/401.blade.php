@extends('errors.layout')

@section('title', 'Inicia sesión')
@section('code', '401')
@section('heading', 'Inicia sesión para continuar')
@section('message')
    Esta página es privada. Ingresa con tu cuenta y te llevamos de vuelta.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ $loginUrl }}">Ingresar</a>
    <a class="btn btn-secondary" href="{{ $homeUrl }}">Ir al inicio</a>
@endsection
