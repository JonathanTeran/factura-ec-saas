@extends('errors.layout')

@php $status = isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400; @endphp

@section('title', 'No pudimos procesar la solicitud')
@section('code', $status)
@section('heading', 'No pudimos procesar la solicitud')
@section('message')
    Algo en la petición no es válido. Vuelve atrás e inténtalo de nuevo; si el problema sigue, escríbenos.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ $panelUrl }}">Ir a mi panel</a>
    <a class="btn btn-secondary" href="{{ $homeUrl }}">Ir al inicio</a>
@endsection
