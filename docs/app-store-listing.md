# Ficha de App Store — Facturón EC

Todo listo para copiar/pegar en **App Store Connect**. Respeta los límites de
caracteres de cada campo.

---

## Datos básicos

| Campo | Valor |
|---|---|
| **Nombre** (máx 30) | `Facturón EC` |
| **Subtítulo** (máx 30) | `Facturación electrónica SRI` |
| **Bundle ID** | `com.facturaec.facturonEc` |
| **Categoría principal** | Negocios (Business) |
| **Categoría secundaria** | Finanzas (Finance) |
| **Clasificación por edad** | 4+ |
| **Idioma principal** | Español (México) o Español (España) |
| **Precio** | Gratis (con suscripción dentro de la app) |
| **URL de soporte** | https://facturacion.amephia.com |
| **URL de marketing** (opcional) | https://amephia.com |
| **URL de política de privacidad** | https://facturacion.amephia.com/privacy |

---

## Texto promocional (máx 170)
> Emití facturas, notas de crédito/débito, retenciones y guías de remisión
> autorizadas por el SRI desde tu celular. Firma electrónica y todo en segundos.

---

## Descripción (máx 4000)

```
Facturón EC es la forma más rápida de emitir tus comprobantes electrónicos del
SRI directo desde el celular. Pensado para emprendedores, pymes y profesionales
del Ecuador que quieren facturar sin complicaciones.

Emití en segundos y con validación del SRI:
• Factura
• Nota de Crédito y Nota de Débito
• Comprobante de Retención
• Guía de Remisión
• Liquidación de Compra

TODO LO QUE NECESITÁS, EN UNA APP
• Autocompletá los datos de tus clientes con solo el RUC o la cédula (consulta
  al catastro del SRI).
• Cargá varios productos por factura, con precio editable, descuentos e IVA.
• Formas de pago y plazos según el catálogo oficial del SRI.
• Firma electrónica: subí tu certificado .p12 y quedá listo para emitir.
• Enviá al SRI y recibí el estado de autorización al instante; si hay
  observaciones, te mostramos el motivo exacto.
• Gestioná tu catálogo de clientes y productos: crear, editar, activar o
  desactivar.

PENSADA PARA EL DÍA A DÍA
• Pantalla de inicio con lo facturado del mes, tendencia y tus documentos
  recientes.
• Buscá comprobantes y clientes al instante.
• Interfaz clara, rápida y en español.
• Acceso con Face ID / huella.

SEGURA Y CONFIABLE
• Tu certificado y tus datos viajan cifrados.
• Multiempresa: manejá varias compañías desde la misma cuenta.

Facturón EC es un producto de AmePhia. Requiere una cuenta activa; podés
gestionar tu plan desde la app. Consultá términos y privacidad en
facturacion.amephia.com.
```

---

## Palabras clave (máx 100, sin espacios tras las comas)

```
comprobante,RIDE,retencion,nota credito,nota debito,guia remision,RUC,pymes,contador,Ecuador
```

> Nota: no repitas “factura”, “SRI” ni “electrónica” aquí — ya están en el
> nombre/subtítulo y Apple los indexa juntos. Estas keywords suman los demás
> términos por los que te pueden buscar.

---

## Novedades de esta versión (What's New)

```
¡Primera versión de Facturón EC! Emití facturas y todos tus comprobantes del
SRI desde el celular, con firma electrónica, autocompletado por RUC/cédula y
estado de autorización en tiempo real.
```

---

## Capturas de pantalla (las tomás vos en el iPhone)

**Tamaños que pide App Store Connect** (subí al menos uno de cada):
- **6.7"** — iPhone 15/16 Pro Max → 1290 × 2796 px
- **6.5"** — iPhone 14 Plus / 11 Pro Max → 1284 × 2778 px (o 1242 × 2688)

Con tu iPhone alcanza: tomá los screenshots en tu equipo (botón lateral +
volumen) y App Store Connect acepta ese tamaño; si pide otro, se pueden
reescalar.

**Guion sugerido (5–6 capturas, en este orden):**
1. **Inicio** — “Hola, …”, tarjeta azul de facturación del mes + gráfico.
   *Caption:* “Tu facturación del mes, de un vistazo”.
2. **Menú Crear** (tocá ＋ Crear) — los 6 tipos de comprobante.
   *Caption:* “Emití cualquier comprobante del SRI”.
3. **Nueva Factura** — con cliente CONSUMIDOR FINAL y productos cargados.
   *Caption:* “Cargá varios productos y cobrá en segundos”.
4. **Autocompletar SRI** — el buscador de cliente / la lupa del RUC.
   *Caption:* “Autocompletá clientes con el RUC o la cédula”.
5. **Resultado SRI** — “¡Documento autorizado!” con la autorización.
   *Caption:* “Autorización del SRI al instante”.
6. **Firma electrónica** (Menú → Firma) — certificado vigente.
   *Caption:* “Subí tu firma .p12 y listo”.

> Tip: hacé las capturas con datos reales o de prueba que se vean prolijos
> (nombres, montos). Evitá datos sensibles de terceros.

---

## App Privacy (cuestionario de privacidad)

Datos que la app recopila y su uso (declarar en App Store Connect):
- **Información de contacto** (nombre, email) — Funcionalidad de la app.
  Vinculada a la identidad. No para seguimiento.
- **Identificadores** (cuenta de usuario) — Funcionalidad. Vinculada.
- **Datos de uso/diagnóstico** — solo si activás analítica; si no, marcá “No”.
- **No** se usa ningún dato para **rastreo (tracking)** entre apps.

(El certificado .p12 y las credenciales del SRI se guardan cifrados y no se
comparten con terceros.)

---

## Checklist antes de enviar a revisión
- [ ] App creada en App Store Connect con el bundle `com.facturaec.facturonEc`.
- [ ] Build 1.0.0 (1) visible en TestFlight/versión.
- [ ] Capturas 6.7" y 6.5" subidas.
- [ ] Descripción, subtítulo, keywords, promo, soporte y privacidad cargados.
- [ ] Cuestionario de App Privacy completo.
- [ ] Cuenta de demo para el revisor (usuario/clave de prueba) en “Notas de
      revisión” — Apple necesita entrar para probar la emisión.
```
```

> **Importante para la revisión:** Apple pide una **cuenta de prueba** para
> poder entrar y probar. Creá un usuario demo con una empresa configurada y
> firma cargada (ambiente de pruebas del SRI) y ponelo en “App Review
> Information → Sign-In required → usuario/clave”.
