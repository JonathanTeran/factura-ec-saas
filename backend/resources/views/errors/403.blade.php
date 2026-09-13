@extends('errors.layout')

@php
    $invalidLink = isset($exception) && $exception instanceof \Illuminate\Routing\Exceptions\InvalidSignatureException;
    $newLinkUrl = $isAdminArea ? url('/admin/password-reset/request') : $frontendUrl.'/forgot-password';
    $detail = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $showDetail = $detail !== '' && ! $invalidLink && ! in_array(strtolower($detail), ['forbidden', 'unauthorized', 'this action is unauthorized.'], true);
@endphp

@section('title', $invalidLink ? 'Enlace no válido' : 'Acceso denegado')
@section('code', '403')
@section('heading', $invalidLink ? 'Este enlace ya no es válido' : 'No tienes acceso a esta página')
@section('message')
    @if($invalidLink)
        Los enlaces que enviamos por correo caducan o dejan de servir cuando se pide uno nuevo. Solicita otro y usa el correo más reciente.
    @elseif($showDetail)
        {{ $detail }}
    @else
        Tu cuenta no tiene permiso para ver esto. Si crees que es un error, escríbenos y lo revisamos.
    @endif
@endsection
@section('actions')
    @if($invalidLink)
        <a class="btn btn-primary" href="{{ $newLinkUrl }}">Pedir un enlace nuevo</a>
        <a class="btn btn-secondary" href="{{ $loginUrl }}">Ingresar</a>
    @else
        <a class="btn btn-primary" href="{{ $panelUrl }}">Ir a mi panel</a>
        <a class="btn btn-secondary" href="{{ $homeUrl }}">Ir al inicio</a>
    @endif
@endsection
