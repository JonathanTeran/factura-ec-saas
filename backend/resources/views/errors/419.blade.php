@extends('errors.layout')

@section('title', 'La página expiró')
@section('code', '419')
@section('heading', 'La página expiró')
@section('message')
    Pasó demasiado tiempo sin actividad y, por seguridad, la sesión de este formulario caducó. Vuelve atrás e inténtalo de nuevo.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url()->previous() !== url()->current() ? url()->previous() : $panelUrl }}">Volver a intentarlo</a>
    <a class="btn btn-secondary" href="{{ $loginUrl }}">Ingresar</a>
@endsection
