# Facturón EC — Kit de publicación en Google Play

Todo lo que necesitas para subir la app. Los archivos están en `store/assets/`
y `store/screenshots/`.

---

## 1. Datos técnicos (ya configurados)

| Campo | Valor |
|---|---|
| Application ID | `com.facturaec.facturonec` (permanente, no cambiar) |
| Versión | `1.0.0 (1)` — se sube desde `pubspec.yaml` (`version: 1.0.0+1`) |
| AAB firmado | `build/app/outputs/bundle/release/app-release.aab` |
| Keystore de subida | `~/desarrollo/keys/facturon-upload.jks` (alias `upload`) |
| Contraseña keystore | `~/desarrollo/keys/facturon-upload.INFO.txt` |

> ⚠️ **RESPALDA EL KEYSTORE Y SU CONTRASEÑA** (iCloud/1Password). Si los
> pierdes, no podrás actualizar la app. Recomendado: en Play Console activa
> **"Firma de aplicaciones de Play"** (Google guarda la llave final y la tuya
> es solo de subida — así una pérdida es recuperable).

Para releases futuros: sube `version:` en `pubspec.yaml` (ej. `1.0.1+2` — el
`+2` es el versionCode y SIEMPRE debe aumentar) y ejecuta
`flutter build appbundle --release`.

---

## 2. Ficha de la tienda (copiar y pegar)

**Nombre de la app** (máx. 30):
```
Facturón EC — Facturación SRI
```

**Descripción corta** (máx. 80):
```
Factura electrónica SRI en segundos: emite, cobra y comparte desde tu celular.
```

**Descripción completa** (máx. 4000):
```
Facturón EC es la forma más simple de manejar tu facturación electrónica del
SRI en Ecuador, directamente desde tu celular.

EMITE EN SEGUNDOS
• Facturas, notas de crédito y débito, liquidaciones de compra, retenciones y
  guías de remisión — los 6 comprobantes del SRI.
• Autorización en línea con el SRI y seguimiento del estado en tiempo real.
• Comparte el PDF por WhatsApp, correo o enlace con un toque.

PUNTO DE VENTA (POS)
• Abre y cierra caja desde el celular.
• Registra ventas rápidas en efectivo, tarjeta o transferencia, con cálculo
  de cambio automático.

TODO TU NEGOCIO EN UNA APP
• Proformas: cotiza, envía y conviértelas en factura cuando te acepten.
• Clientes y productos con búsqueda instantánea.
• Reportes de ventas e IVA.
• Configuración completa: empresa, establecimientos, secuenciales, firma
  electrónica (.p12) y plantillas de correo — sin necesidad de la web.

PENSADA PARA ECUADOR
• Validaciones del SRI antes de enviar (cédula/RUC, montos de consumidor
  final) para que tus comprobantes no sean rechazados.
• Ambiente de pruebas para aprender sin riesgo y migración guiada a
  producción.
• Notificaciones y reenvío de comprobantes a tus clientes.

Facturón EC es un producto de AmePhia. Requiere una cuenta (prueba gratuita
disponible en facturacion.amephia.com) y tu certificado de firma electrónica
para emitir comprobantes con validez tributaria.

Soporte: jo.teran3@gmail.com · https://facturacion.amephia.com
```

**Categoría:** Negocios (Business)
**Etiquetas sugeridas:** facturación, SRI, Ecuador, factura electrónica, POS

**Datos de contacto del desarrollador:**
- Email: jo.teran3@gmail.com (obligatorio; visible en la ficha)
- Sitio web: https://facturacion.amephia.com

**Política de privacidad (obligatoria):**
```
https://facturacion.amephia.com/privacy
```

---

## 3. Gráficos (en `store/assets/`)

| Archivo | Uso | Requisito |
|---|---|---|
| `icon-512.png` | Ícono de la ficha | 512×512 PNG ✓ |
| `feature-graphic.png` | Gráfico de funciones | 1024×500 PNG ✓ |
| `../screenshots-framed/*.png` | **Capturas para subir** (marco + titular, 1080×1920) | mín. 2, 9:16 ✓ |
| `../screenshots/*.png` | Capturas crudas (respaldo/edición) | 1080×2400 |

---

## 4. Formulario "Seguridad de los datos" (respuestas)

Google pregunta qué datos recolecta la app. Respuestas para Facturón EC:

- **¿Recopila o comparte datos del usuario?** → Sí, recopila. No comparte con
  terceros.
- **Datos recopilados:**
  - *Información personal*: nombre, correo electrónico, número de teléfono
    (perfil de la cuenta) — Obligatorio, para funcionalidad de la app.
  - *Información financiera*: registros de transacciones (los comprobantes que
    el usuario emite) — Obligatorio, funcionalidad de la app.
  - *Fotos*: solo si el usuario adjunta comprobante de transferencia o logo —
    Opcional, funcionalidad de la app.
- **¿Los datos se cifran en tránsito?** → Sí (HTTPS).
- **¿El usuario puede solicitar la eliminación?** → Sí (la app tiene
  "Eliminar cuenta" en Menú, y por correo de soporte).

## 5. Clasificación de contenido (cuestionario IARC)

- Tipo de app: **Utilidad / productividad / comunicación**
- Violencia/sexo/lenguaje/drogas/apuestas: **No** a todo.
- ¿Compras digitales? **No dentro de la app** (la suscripción se paga por
  transferencia fuera de la app). → Resultado esperado: **PEGI 3 / Everyone**.

## 6. Público objetivo

- Grupo etario: **18+** (herramienta de negocios).
- ¿Dirigida a niños? **No**.

---

## 7. Pasos en Play Console (una sola vez)

1. Crea la cuenta de desarrollador en https://play.google.com/console
   (pago único de $25, necesitas una cuenta Google y tarjeta).
2. **Crear app** → Nombre: `Facturón EC — Facturación SRI`, idioma
   predeterminado: Español (Latinoamérica), tipo: App, gratis.
3. En **Configuración → Firma de aplicaciones**, acepta que Google administre
   la llave de firma (recomendado).
4. Completa la sección **Panel → Configura tu app**:
   - Política de privacidad → URL de arriba.
   - Acceso a la app → "Todo el contenido está disponible sin acceso especial"
     NO aplica (hay login): elige "Todo o parte de la app requiere acceso" y
     agrega credenciales de un usuario demo para el equipo de revisión de
     Google (crea una cuenta de prueba en tu sistema para esto).
   - Anuncios → No contiene anuncios.
   - Clasificación de contenido → cuestionario (sección 5).
   - Público objetivo → 18+.
   - Seguridad de los datos → sección 4.
5. **Ficha de la tienda principal** → pega textos (sección 2), sube
   `icon-512.png`, `feature-graphic.png` y las capturas.
6. **Producción → Crear versión** → sube
   `build/app/outputs/bundle/release/app-release.aab` → nombre de versión
   `1.0.0` → notas de la versión:
   ```
   Primera versión: emisión de comprobantes SRI, POS, proformas, clientes,
   productos, reportes y configuración completa desde el celular.
   ```
7. **Enviar a revisión.** La primera revisión tarda de 1 a 7 días.

> Nota: para cuentas de desarrollador personales creadas recientemente,
> Google exige una **prueba cerrada con 12 testers durante 14 días** antes de
> poder publicar en producción. Si te aparece ese requisito, crea primero una
> "Prueba cerrada", invita testers (correos de conocidos) y luego promociona
> a producción.
