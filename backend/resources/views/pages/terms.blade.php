<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminos de Servicio - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.8; color: #1f2937; background: #f9fafb; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #fff; border-radius: 12px; padding: 48px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 28px; color: #111827; margin-bottom: 8px; }
        .date { color: #6b7280; font-size: 14px; margin-bottom: 32px; }
        h2 { font-size: 20px; color: #111827; margin-top: 32px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        p, li { font-size: 15px; color: #374151; }
        ul { padding-left: 24px; }
        a { color: #0284c7; }
        .back { display: inline-block; margin-bottom: 24px; color: #6b7280; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <a href="{{ url('/') }}" class="back">&larr; Volver al inicio</a>
    <div class="card">
        <h1>Terminos de Servicio</h1>
        <p class="date">Ultima actualizacion: {{ now()->format('d/m/Y') }}</p>

        <h2>1. Aceptacion de los Terminos</h2>
        <p>Al acceder y utilizar la plataforma {{ config('app.name') }} (en adelante "el Servicio"), usted acepta estos Terminos de Servicio. Si no esta de acuerdo, no utilice el Servicio.</p>

        <h2>2. Descripcion del Servicio</h2>
        <p>{{ config('app.name') }} es una plataforma SaaS de facturacion electronica que permite:</p>
        <ul>
            <li>Emision de comprobantes electronicos (facturas, notas de credito, notas de debito, guias de remision y comprobantes de retencion) autorizados por el Servicio de Rentas Internas (SRI) del Ecuador.</li>
            <li>Gestion de clientes, productos e inventario.</li>
            <li>Generacion de reportes tributarios incluyendo el Anexo Transaccional Simplificado (ATS).</li>
            <li>Acceso mediante aplicacion web y movil.</li>
        </ul>

        <h2>3. Registro y Cuentas</h2>
        <ul>
            <li>Debe proporcionar informacion veraz y actualizada, incluyendo su RUC, razon social y datos de contacto.</li>
            <li>Es responsable de mantener la confidencialidad de sus credenciales de acceso.</li>
            <li>Debe notificarnos inmediatamente sobre cualquier uso no autorizado de su cuenta.</li>
        </ul>

        <h2>4. Firma Electronica y Certificados Digitales</h2>
        <ul>
            <li>El usuario es responsable de obtener y mantener vigente su firma electronica (certificado digital P12/PFX) emitida por una entidad certificadora autorizada en Ecuador.</li>
            <li>La contrasena de la firma electronica se almacena de forma cifrada (AES-256-CBC) y nunca es accesible en texto plano.</li>
            <li>{{ config('app.name') }} no se responsabiliza por el uso indebido de firmas electronicas ni por certificados expirados.</li>
        </ul>

        <h2>5. Obligaciones del Usuario</h2>
        <ul>
            <li>Garantizar la veracidad de la informacion fiscal ingresada en los comprobantes electronicos.</li>
            <li>Cumplir con las obligaciones tributarias establecidas por el SRI y la normativa ecuatoriana vigente.</li>
            <li>No utilizar el Servicio para actividades ilicitas o contrarias a la ley.</li>
            <li>Mantener actualizada la informacion de su empresa, sucursales y puntos de emision.</li>
        </ul>

        <h2>6. Responsabilidad sobre Documentos Electronicos</h2>
        <ul>
            <li>{{ config('app.name') }} actua como intermediario tecnologico entre el usuario y el SRI.</li>
            <li>La responsabilidad tributaria de los comprobantes electronicos recae exclusivamente en el emisor (usuario).</li>
            <li>No garantizamos la autorizacion de documentos por parte del SRI cuando la informacion proporcionada sea incorrecta o incompleta.</li>
        </ul>

        <h2>7. Planes y Pagos</h2>
        <ul>
            <li>Los planes de suscripcion tienen limites de documentos mensuales, usuarios y empresas segun el nivel contratado.</li>
            <li>Los pagos se realizan de forma mensual o anual segun el ciclo elegido.</li>
            <li>En caso de falta de pago, el acceso al Servicio podra ser suspendido tras un periodo de gracia de 7 dias.</li>
            <li>Los precios pueden ser modificados con un aviso previo de 30 dias.</li>
        </ul>

        <h2>8. Periodo de Prueba</h2>
        <p>Ofrecemos un periodo de prueba gratuito. Al finalizar, debera suscribirse a un plan pago para continuar utilizando el Servicio.</p>

        <h2>9. Cancelacion</h2>
        <ul>
            <li>Puede cancelar su suscripcion en cualquier momento desde la configuracion de su cuenta.</li>
            <li>Tras la cancelacion, mantendra acceso hasta el final del periodo facturado.</li>
            <li>Los documentos electronicos emitidos y sus archivos XML permanecen disponibles por un periodo minimo de 7 anos conforme a la normativa tributaria ecuatoriana.</li>
        </ul>

        <h2>10. Propiedad Intelectual</h2>
        <p>Todo el software, diseno, marcas y contenido de {{ config('app.name') }} son propiedad exclusiva de sus creadores. Los documentos electronicos generados son propiedad del usuario emisor.</p>

        <h2>11. Proteccion de Datos</h2>
        <p>El tratamiento de datos personales se rige por nuestra <a href="{{ route('privacy') }}">Politica de Privacidad</a> y la Ley Organica de Proteccion de Datos Personales del Ecuador (LOPDP).</p>

        <h2>12. Limitacion de Responsabilidad</h2>
        <ul>
            <li>{{ config('app.name') }} no sera responsable por interrupciones del servicio del SRI.</li>
            <li>No asumimos responsabilidad por perdidas economicas derivadas del uso incorrecto del Servicio.</li>
            <li>Nuestra responsabilidad maxima se limita al monto pagado por el usuario en los ultimos 12 meses.</li>
        </ul>

        <h2>13. Disponibilidad del Servicio</h2>
        <p>Nos esforzamos por mantener una disponibilidad del 99.9%. Pueden ocurrir interrupciones programadas para mantenimiento, las cuales seran notificadas con anticipacion.</p>

        <h2>14. Ley Aplicable y Jurisdiccion</h2>
        <p>Estos terminos se rigen por las leyes de la Republica del Ecuador. Cualquier controversia sera resuelta ante los jueces competentes de la ciudad de Quito.</p>

        <h2>15. Modificaciones</h2>
        <p>Nos reservamos el derecho de modificar estos terminos. Los cambios seran notificados por correo electronico y/o mediante aviso en la plataforma con al menos 15 dias de anticipacion.</p>

        <h2>16. Contacto</h2>
        <p>Para consultas sobre estos terminos, contactenos a <a href="mailto:soporte@factura-ec.com">soporte@factura-ec.com</a>.</p>
    </div>
</div>
</body>
</html>
