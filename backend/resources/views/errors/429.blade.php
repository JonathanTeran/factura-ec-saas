@extends('errors.layout')

@php $retryAfter = (int) (isset($exception) && method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? 0) : 0); @endphp

@section('title', 'Demasiadas solicitudes')
@section('code', '429')
@section('heading', 'Vamos con calma')
@section('message')
    Recibimos demasiadas solicitudes desde tu conexión en poco tiempo.
    @if($retryAfter > 0) Espera {{ $retryAfter }} segundos e inténtalo de nuevo. @else Espera un momento e inténtalo de nuevo. @endif
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url()->current() }}">Reintentar</a>
    <a class="btn btn-secondary" href="{{ $homeUrl }}">Ir al inicio</a>
@endsection
