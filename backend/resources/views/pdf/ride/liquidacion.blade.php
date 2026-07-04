<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Liquidación de compra {{ $documentNumber }}</title>
    @include('pdf.ride.partials.styles')
</head>
<body>
    @include('pdf.ride.partials.header', ['docTitle' => 'LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS'])
    @include('pdf.ride.partials.customer', ['partyTitle' => 'Datos del proveedor'])
    @include('pdf.ride.partials.items')
    @include('pdf.ride.partials.totals')
    @include('pdf.ride.partials.footer', ['skipInfo' => true])
</body>
</html>
