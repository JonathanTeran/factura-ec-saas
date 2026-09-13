@extends('errors.layout')

@php $status = isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500; @endphp

@section('title', 'Problema temporal')
@section('code', $status)
@section('heading', 'Tenemos un problema temporal')
@section('message')
    Ya estamos al tanto. Inténtalo de nuevo en unos minutos; tus datos están a salvo.
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url()->current() }}">Reintentar</a>
    <a class="btn btn-secondary" href="{{ $homeUrl }}">Ir al inicio</a>
@endsection
