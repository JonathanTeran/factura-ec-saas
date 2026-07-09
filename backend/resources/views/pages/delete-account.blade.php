<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminación de cuenta - Facturón EC</title>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.8; color: #1f2937; background: #f9fafb; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #fff; border-radius: 12px; padding: 48px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 28px; color: #111827; margin-bottom: 8px; }
        .date { color: #6b7280; font-size: 14px; margin-bottom: 32px; }
        h2 { font-size: 20px; color: #111827; margin-top: 32px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        p, li { font-size: 15px; color: #374151; }
        ul, ol { padding-left: 24px; }
        a { color: #0284c7; }
        .back { display: inline-block; margin-bottom: 24px; color: #6b7280; text-decoration: none; font-size: 14px; }
        .note { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 14px 18px; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <a href="{{ url('/') }}" class="back">&larr; Volver al inicio</a>
    <div class="card">
        <h1>Eliminación de cuenta y datos</h1>
        <p class="date">Facturón EC — un producto de AmePhia · Última actualización: {{ now()->format('d/m/Y') }}</p>

        <p>Esta página explica cómo eliminar tu cuenta de <strong>Facturón EC</strong>
        (aplicación de facturación electrónica SRI publicada por AmePhia) y qué ocurre
        con tus datos al hacerlo.</p>

        <h2>1. Eliminar tu cuenta desde la aplicación</h2>
        <ol>
            <li>Abre la aplicación <strong>Facturón EC</strong> e inicia sesión.</li>
            <li>Ve a la pestaña <strong>Menú</strong>.</li>
            <li>Baja hasta la sección de configuración y toca <strong>«Eliminar cuenta»</strong>.</li>
            <li>Confirma tu contraseña para completar la eliminación.</li>
        </ol>

        <h2>2. Eliminar tu cuenta por correo</h2>
        <p>Si prefieres no usar la aplicación, escribe a
        <a href="mailto:jo.teran3@gmail.com">jo.teran3@gmail.com</a> desde el correo
        registrado en tu cuenta, con el asunto «Eliminación de cuenta». Procesaremos
        la solicitud en un máximo de <strong>15 días</strong>.</p>

        <h2>3. Qué datos se eliminan</h2>
        <ul>
            <li>Tu cuenta de usuario (nombre, correo, teléfono y credenciales).</li>
            <li>Los datos de tu empresa, clientes, productos, proformas y borradores.</li>
            <li>Tu certificado de firma electrónica (.p12) almacenado.</li>
            <li>Comprobantes de pago y archivos adjuntos.</li>
        </ul>

        <h2>4. Qué datos se conservan y por cuánto tiempo</h2>
        <p class="note">Los <strong>comprobantes electrónicos autorizados por el SRI</strong>
        (facturas, notas de crédito/débito, retenciones, etc.) son documentos tributarios:
        la normativa ecuatoriana obliga a conservarlos por <strong>7 años</strong>.
        Durante ese periodo se mantienen archivados de forma segura y aislada, y no se
        usan para ningún otro fin. Los registros de pagos de la suscripción se conservan
        el tiempo exigido por la normativa contable.</p>

        <p>El resto de los datos se elimina de forma permanente en un plazo máximo de
        30 días desde la solicitud.</p>

        <h2>5. Contacto</h2>
        <p>Dudas sobre este proceso: <a href="mailto:jo.teran3@gmail.com">jo.teran3@gmail.com</a>
        · <a href="{{ url('/privacy') }}">Política de privacidad</a></p>
    </div>
</div>
</body>
</html>
