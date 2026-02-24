<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politica de Privacidad - {{ config('app.name') }}</title>
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
        <h1>Politica de Privacidad</h1>
        <p class="date">Ultima actualizacion: {{ now()->format('d/m/Y') }}</p>

        <p>{{ config('app.name') }} se compromete a proteger la privacidad de sus usuarios conforme a la Ley Organica de Proteccion de Datos Personales del Ecuador (LOPDP).</p>

        <h2>1. Datos que Recopilamos</h2>
        <p><strong>Datos de registro:</strong></p>
        <ul>
            <li>Nombre completo y correo electronico</li>
            <li>Nombre de la empresa y RUC</li>
            <li>Telefono y direccion (opcional)</li>
        </ul>
        <p><strong>Datos fiscales:</strong></p>
        <ul>
            <li>Razon social, RUC, tipo de contribuyente</li>
            <li>Informacion de sucursales y puntos de emision</li>
            <li>Datos de clientes y proveedores ingresados por el usuario</li>
            <li>Contenido de comprobantes electronicos (facturas, retenciones, etc.)</li>
        </ul>
        <p><strong>Firma electronica:</strong></p>
        <ul>
            <li>Archivo del certificado digital (P12/PFX)</li>
            <li>Contrasena del certificado (almacenada con cifrado AES-256-CBC)</li>
            <li>Metadatos: emisor, sujeto, fecha de expiracion</li>
        </ul>
        <p><strong>Datos de uso:</strong></p>
        <ul>
            <li>Registros de actividad (logs) para auditoria y seguridad</li>
            <li>Direccion IP y agente de usuario del navegador</li>
            <li>Informacion de sesion y preferencias</li>
        </ul>

        <h2>2. Finalidad del Tratamiento</h2>
        <ul>
            <li><strong>Prestacion del servicio:</strong> Generacion, firma y envio de comprobantes electronicos al SRI.</li>
            <li><strong>Gestion de cuenta:</strong> Administracion de suscripciones, facturacion y soporte.</li>
            <li><strong>Cumplimiento legal:</strong> Conservacion de registros tributarios conforme a la normativa ecuatoriana.</li>
            <li><strong>Mejora del servicio:</strong> Analisis de uso para optimizar la plataforma.</li>
            <li><strong>Comunicaciones:</strong> Notificaciones sobre documentos, suscripciones y actualizaciones del servicio.</li>
        </ul>

        <h2>3. Almacenamiento y Seguridad</h2>
        <ul>
            <li>Los datos se almacenan en servidores seguros con cifrado en transito (TLS) y en reposo.</li>
            <li>Los certificados digitales se almacenan cifrados con AES-256-CBC en almacenamiento aislado (S3).</li>
            <li>Cada cuenta de empresa (tenant) tiene aislamiento logico de datos.</li>
            <li>Realizamos copias de seguridad periodicas de toda la informacion.</li>
            <li>El acceso a datos esta restringido por roles y permisos.</li>
        </ul>

        <h2>4. Comparticion de Datos</h2>
        <p>Sus datos <strong>solo</strong> se comparten con:</p>
        <ul>
            <li><strong>Servicio de Rentas Internas (SRI):</strong> Envio de comprobantes electronicos en formato XML firmado, conforme a la obligacion tributaria.</li>
            <li><strong>Pasarelas de pago:</strong> Datos de facturacion necesarios para procesar pagos (Stripe, PayPhone). No almacenamos datos completos de tarjetas.</li>
            <li><strong>Proveedores de infraestructura:</strong> Servicios de hosting y almacenamiento que cumplen con estandares de seguridad.</li>
        </ul>
        <p>No vendemos, alquilamos ni compartimos sus datos con terceros para fines de marketing.</p>

        <h2>5. Retencion de Datos</h2>
        <ul>
            <li><strong>Comprobantes electronicos y XML:</strong> 7 anos minimo (obligacion tributaria Art. 55 LRTI).</li>
            <li><strong>Datos de cuenta:</strong> Mientras la cuenta este activa y hasta 1 ano despues de la cancelacion.</li>
            <li><strong>Certificados digitales:</strong> Hasta su expiracion o reemplazo.</li>
            <li><strong>Logs de actividad:</strong> 90 dias.</li>
        </ul>

        <h2>6. Derechos del Titular</h2>
        <p>Conforme a la LOPDP, usted tiene derecho a:</p>
        <ul>
            <li><strong>Acceso:</strong> Solicitar copia de sus datos personales.</li>
            <li><strong>Rectificacion:</strong> Corregir datos inexactos o incompletos.</li>
            <li><strong>Eliminacion:</strong> Solicitar la eliminacion de datos (sujeto a obligaciones legales de retencion).</li>
            <li><strong>Oposicion:</strong> Oponerse al tratamiento de datos para fines no esenciales.</li>
            <li><strong>Portabilidad:</strong> Solicitar sus datos en formato legible por maquina.</li>
        </ul>
        <p>Para ejercer estos derechos, contactenos a <a href="mailto:privacidad@factura-ec.com">privacidad@factura-ec.com</a>.</p>

        <h2>7. Cookies</h2>
        <p>Utilizamos cookies esenciales para el funcionamiento de la plataforma (sesion, autenticacion, preferencias). No utilizamos cookies de seguimiento ni publicidad.</p>

        <h2>8. Menores de Edad</h2>
        <p>El Servicio esta dirigido a personas mayores de 18 anos. No recopilamos intencionalmente datos de menores.</p>

        <h2>9. Transferencias Internacionales</h2>
        <p>Si los datos se procesan fuera de Ecuador (por ejemplo, servicios de infraestructura en la nube), nos aseguramos de que el proveedor cumpla con estandares de proteccion equivalentes conforme a la LOPDP.</p>

        <h2>10. Modificaciones</h2>
        <p>Nos reservamos el derecho de actualizar esta politica. Notificaremos cambios significativos por correo electronico.</p>

        <h2>11. Contacto</h2>
        <p>Responsable de proteccion de datos:<br>
        Correo: <a href="mailto:privacidad@factura-ec.com">privacidad@factura-ec.com</a></p>
    </div>
</div>
</body>
</html>
