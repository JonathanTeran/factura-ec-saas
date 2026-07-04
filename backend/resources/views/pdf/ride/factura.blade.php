<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $documentNumber }}</title>
    @include('pdf.ride.partials.styles')
</head>
<body>
    @include('pdf.ride.partials.header', ['docTitle' => 'FACTURA'])
    @include('pdf.ride.partials.customer')
    @include('pdf.ride.partials.items')
    @include('pdf.ride.partials.totals')
    @include('pdf.ride.partials.footer', ['skipInfo' => true])
</body>
</html>
