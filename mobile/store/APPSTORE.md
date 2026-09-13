# Facturón EC — Kit de publicación en App Store (iOS)

> Complemento iOS de `LISTING.md` (que cubre Google Play). Los assets viven en
> `store/assets/` y `store/screenshots-ios/`.

---

## ✅ Cuenta de publicación: equipo de Alexis Polo

Los Apple ID propios (jonaterandev@icloud.com → `XNJUKKZ8N6`, jo-teran@hotmail.com
→ `5PGSLKQM49`) son **Personal Teams gratuitos** y no pueden publicar. El
2026-09-12 se decidió publicar con el único equipo de pago disponible:
**`TG2383G93A` — Alexis Polo (cuenta Individual)**.

Consecuencias aceptadas:
- La App Store muestra **"Alexis Polo"** como vendedor y la app vive en esa
  cuenta. Moverla después a una cuenta propia requiere una transferencia de app.
- Al ser cuenta individual sigue el riesgo de la guía 5.1.1(ix): Apple pide que
  las apps financieras o con datos sensibles las publique una entidad legal.

`com.facturaec.facturonEc` y `com.amephia.facturon` quedaron registrados
temporalmente en los teams gratuitos; por eso la app usa
**`com.amephia.facturonec`**. No compilar este proyecto con los teams gratuitos:
cada build con aprovisionamiento automático registra identificadores.

---

## 0. ✅ Regla 3.1.1 — resuelto

`lib/features/settings/billing_screen.dart` es un **flujo de compra dentro de
la app**: elegir plan → ver cuentas bancarias → subir comprobante de
transferencia → "activaremos tu plan". Apple rechaza esto de forma casi
automática bajo la **guía 3.1.1**.

La excepción que nos sirve es la **3.1.3(d) — Free Standalone Apps**:

> "Free standalone apps that serve as companions to paid web based tools
> (e.g. VOIP, Cloud Storage, Email Services, Web Hosting) do not need to use
> in-app purchase, **provided there is no purchasing inside the app, or calls
> to action for purchase outside of the app**."

Facturón EC califica como companion de un servicio web de pago — **pero solo
si la app no vende nada dentro**. El gate ya está implementado en
`lib/core/utils/store_policy.dart` (`billingFlowAvailable`) y oculta en iOS:

| Ubicación | Qué se ocultó | Estado |
|---|---|---|
| `lib/features/settings/settings_screen.dart` | Tile "Facturación — Plan, pagos y transferencia bancaria." | ✅ |
| `lib/features/reports/reports_screen.dart` | Botón "Actualizar plan" (paywall de reportes) | ✅ |
| `lib/core/router/app_router.dart` | Ruta `/settings/billing` — ni se registra | ✅ |

En Android y en la build web queda todo igual: no se borró código. Se usa
`defaultTargetPlatform` y no `Platform.isIOS` porque `dart:io` no existe en
web. Blindado por `test/store_policy_test.dart` (2 tests, pasan).

> Detalle menor pendiente de tu criterio: cuando el backend responde 403 en
> Analítica, el mensaje mostrado viene del servidor y puede mencionar el plan.
> No es un flujo de compra ni un enlace, así que no viola 3.1.1, pero conviene
> revisar ese texto.

---

## 1. Datos técnicos

| Campo | Valor |
|---|---|
| Bundle ID | `com.amephia.facturonec` (permanente) |
| Team ID | `TG2383G93A` — Alexis Polo (cuenta Individual, de pago) |
| Versión / build | `1.0.0 (4)` — desde `pubspec.yaml` (`version: 1.0.0+4`) |
| Deployment target | iOS 13.0 |
| Device family | iPhone únicamente (`TARGETED_DEVICE_FAMILY = "1"`) ✅ |
| Cifrado no exento | `ITSAppUsesNonExemptEncryption = false` ✅ ya en Info.plist |
| Manifiesto privacidad | `ios/Runner/PrivacyInfo.xcprivacy` ✅ creado y en el bundle |
| Export options | `ios/ExportOptions.plist` (exportar `.ipa`) y `ios/ExportOptions-upload.plist` (subir) ✅ |

> **iPad**: se pasó a iPhone-only para la v1, así que Apple no pide capturas
> de iPad ni revisa el layout en tablet. Para añadir iPad más adelante basta
> con volver a `"1,2"` y subir capturas de 13".

---

## 2. Ficha de App Store Connect (copiar y pegar)

**Nombre** (máx. 30) — 29 chars:
```
Facturón EC — Facturación SRI
```

**Subtítulo** (máx. 30) — 27 chars:
```
Facturación electrónica SRI
```

**Texto promocional** (máx. 170, editable sin re-revisión):
```
Emite facturas, notas de crédito, retenciones y guías del SRI desde tu
iPhone. POS, proformas y reportes incluidos.
```

**Palabras clave** (máx. 100, sin espacios tras las comas) — 87 chars:
```
RUC,comprobante,retención,proforma,guía remisión,POS,caja,contabilidad,IVA,PYME,Ecuador
```
> No repitas palabras del nombre ("factura", "SRI"): Apple ya las indexa.

**Descripción** (máx. 4000):
```
Facturón EC es la forma más simple de manejar tu facturación electrónica del
SRI en Ecuador, directamente desde tu iPhone.

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
  electrónica (.p12) y plantillas de correo.

PENSADA PARA ECUADOR
• Validaciones antes de enviar (cédula/RUC, montos de consumidor final) para
  que tus comprobantes no sean rechazados.
• Ambiente de pruebas para aprender sin riesgo y migración guiada a
  producción.
• Notificaciones y reenvío de comprobantes a tus clientes.

Facturón EC es un producto de AmePhia. Requiere una cuenta activa de Facturón
EC y tu certificado de firma electrónica para emitir comprobantes con validez
tributaria.

AVISO IMPORTANTE: Facturón EC es una aplicación privada e independiente. NO es
una aplicación oficial del Servicio de Rentas Internas (SRI) del Ecuador ni de
ningún otro organismo gubernamental, y no tiene afiliación, respaldo ni
autorización de dichas entidades. La aplicación se limita a generar y enviar
comprobantes electrónicos a los servidores oficiales del SRI mediante sus
webservices públicos. Para trámites, normativa y consultas directamente con la
entidad, visita el sitio oficial: https://www.sri.gob.ec

Soporte: jo.teran3@gmail.com
```

> Se quitó "prueba gratuita disponible en facturon.ec" respecto de
> la ficha de Play: leído como *call to action* de compra externa, rompe la
> excepción 3.1.3(d).

**URLs:**
- Soporte (obligatoria): `https://facturon.ec`
- Marketing (opcional): `https://facturon.ec`
- Política de privacidad (obligatoria): `https://facturon.ec/privacy` ✅ HTTP 200

**Categoría:** Primaria `Business` · Secundaria `Finance`
**Clasificación por edad:** 4+ (sin contenido objetable)
**Copyright:** `2026 AmePhia`

---

## 3. Capturas de pantalla

| Dispositivo | Tamaño exigido | Estado |
|---|---|---|
| iPhone 6.9" | 1290×2796 | ✅ 9 capturas en `store/screenshots-ios/` |
| iPad 13" | — | No aplica (app iPhone-only) |

Mínimo 3, máximo 10. Apple escala hacia abajo desde 6.9", así que ese único
tamaño cubre todos los iPhone. Súbelas en el orden `01`…`09`; si quieres
acortar la ficha, las más vendedoras son 01, 03, 06 y 09.

Se generaron con `store/assets/ios-screenshots.html` — lienzo exacto de
1290×2796, fondo de marca + titular, recortando la barra de estado de Android
del PNG original. Para regenerarlas tras un cambio de UI, sirve la carpeta y
captura cada `.slide` desde el navegador:

    cd mobile/store && uv run python -m http.server 8912 --bind 127.0.0.1

> Las de `store/screenshots/` y `screenshots-framed/` siguen siendo las de
> Google Play — no se tocaron.

---

## 4. App Privacy (nutrition label)

- **¿Recopila datos?** Sí. **¿Los comparte con terceros?** No.
- **¿Se usan para rastreo/publicidad?** No (`NSPrivacyTracking = false`).

| Categoría | Dato | Vinculado a identidad | Propósito |
|---|---|---|---|
| Contact Info | Nombre, email, teléfono | Sí | Funcionalidad de la app |
| Financial Info | Historial de transacciones (comprobantes emitidos) | Sí | Funcionalidad de la app |
| User Content | Fotos (adjuntos opcionales: logo, comprobantes) | Sí | Funcionalidad de la app |

- Cifrado en tránsito: sí (HTTPS).
- Eliminación de cuenta: sí, in-app (`Menú → Cuenta y seguridad → Eliminar
  cuenta`) — **obligatorio** por guía 5.1.1(v). ✅ ya implementado.

---

## 5. Notas para el revisor (App Review Information)

```
DEMO ACCOUNT
Usuario: <PENDIENTE — crear cuenta demo con datos de prueba>
Clave:   <PENDIENTE>

La app requiere inicio de sesión. La cuenta demo ya viene con empresa,
establecimiento, punto de emisión, clientes y productos cargados, y está en
AMBIENTE DE PRUEBAS del SRI, por lo que se pueden emitir comprobantes sin
efectos tributarios reales.

SOBRE EL MODELO DE NEGOCIO (guía 3.1.3(d))
Facturón EC es una app gratuita y complementaria de un servicio web de pago
para empresas (facturon.ec). No se vende ningún contenido ni
suscripción dentro de la app, y la app no contiene llamadas a la acción para
comprar fuera de ella. Las cuentas se contratan por separado por parte de
empresas que ya cuentan con RUC y certificado de firma electrónica emitido por
una entidad certificadora autorizada del Ecuador.

SOBRE EL SRI
Facturón EC no es una app oficial del Servicio de Rentas Internas del Ecuador
ni tiene afiliación con dicha entidad. Se comunica con los webservices
públicos del SRI para autorizar comprobantes electrónicos, tal como lo hace
cualquier software de facturación autorizado en el país. El aviso está también
en la descripción de la ficha.

PERMISOS
- Cámara: escanear comprobantes.
- Fotos: adjuntar logo de la empresa y comprobantes.
- Face ID: bloqueo opcional de la app.
```

---

## 6. Proceso de subida

### 6.1 Certificado de distribución ✅
Resuelto: con la cuenta del equipo `TG2383G93A` conectada en Xcode, la firma
automática creó **Apple Distribution: Alexis Polo (TG2383G93A)** y el perfil
"iOS Team Store Provisioning Profile: com.amephia.facturonec".

### 6.2 Crear el registro de la app
En https://appstoreconnect.apple.com (cuenta de Alexis Polo) → `Apps` → `+` → **New App**:
- Platforms: **iOS** · Name: `Facturón EC — Facturación SRI`
- Primary Language: **Spanish (Mexico)** — App Store no tiene "Spanish (Latin America)"
- Bundle ID: `com.amephia.facturonec` (aparece como "XC com amephia facturonec")
- SKU: `facturon-ec-ios` · User Access: **Full Access**

### 6.3 Archivar y exportar
```bash
cd mobile
export LANG=en_US.UTF-8 LC_ALL=en_US.UTF-8
flutter clean && flutter pub get
flutter build ipa --release --export-options-plist=ios/ExportOptions.plist
```
El `.ipa` queda en `build/ios/ipa/`.

### 6.4 Subir a TestFlight
La API key disponible (`ApiKey_0M6SPNTC38DS.p8`) es **individual**: sirve para la
API REST, pero `altool` exige un Issuer ID que las claves individuales no tienen.
Por eso la subida usa la sesión de Xcode:

```bash
xcodebuild -exportArchive \
  -archivePath build/ios/archive/Runner.xcarchive \
  -exportOptionsPlist ios/ExportOptions-upload.plist \
  -exportPath build/ios/upload \
  -allowProvisioningUpdates
```

Alternativas: `Xcode → Organizer → Distribute App`, o arrastrar el `.ipa` a
**Transporter**.

### 6.5 Después
1. El build aparece en TestFlight en ~5-15 min (procesamiento).
2. Responder el cuestionario de **Export Compliance** (ya declarado `false`).
3. Probar en tu iPhone vía TestFlight.
4. Completar ficha + capturas + App Privacy + notas del revisor.
5. `Submit for Review`. Primera revisión: típicamente 24-48 h.

---

## 7. Checklist previo al envío

Hecho:
- [x] Flujo de pago oculto en iOS (sección 0) + test que lo blinda
- [x] iPhone-only para la v1 (sección 1)
- [x] 9 capturas 1290×2796 generadas (sección 3)
- [x] `PrivacyInfo.xcprivacy` y `ExportOptions.plist` creados
- [x] Build de release iOS verificado — compila limpio (41 MB, sin firmar)

Hecho además (2026-09-12):
- [x] Equipo de pago: `TG2383G93A` (Alexis Polo)
- [x] Bundle `com.amephia.facturonec` registrado + certificado Apple Distribution
- [x] `.ipa` 1.0.0 (4) firmado y verificado (30.5 MB)

Te toca a ti:
- [ ] ⛔ Registro de la app en App Store Connect (sección 6.2)
- [ ] ⛔ Credenciales de la cuenta demo en las notas del revisor (sección 5)

Después:
- [ ] Subir el build a TestFlight (sección 6.4)
- [ ] Ficha, App Privacy y clasificación de edad completadas
- [ ] Probado en tu iPhone vía TestFlight
- [ ] Enviar a revisión
