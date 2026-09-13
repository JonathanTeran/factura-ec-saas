# Spec — Nueva landing de Facturón en Next.js

> Estado: **aprobado en diseño, pendiente de plan** · Fecha: 2026-09-12 · Autor: Jonathan + Claude
> Alcance: solo la landing pública. La migración del servidor a la otra cuenta de
> DigitalOcean y el cambio de dominio a `facturon.ec` tendrán sus propios specs.

---

## 1. Contexto y objetivo

La landing actual es un Blade de 1123 líneas (`backend/resources/views/welcome.blade.php`)
en teal, con un mock dibujado en vez de capturas reales, links muertos en el footer,
FAQ desactualizado ("app próximamente") y sin datos estructurados. La marca ya es
**navy `#0B1220` + azul `#2B54E4` + acento degradado bandera** (logo SVG en
`mobile/assets/branding/`), y el panel Next.js usa azul `#2563eb`.

Objetivo: una landing **de alto impacto** en `facturon.ec` (hoy `facturacion.amephia.com`)
que convierta visitas en cuentas y que **buscadores y asistentes de IA la encuentren y
la citen** por medios legítimos (SEO + GEO). Sin texto oculto ni instrucciones para
modelos de IA: es engañoso, Google lo penaliza como cloaking y los modelos lo ignoran.

Métricas de éxito: Lighthouse ≥ 90 en Performance, SEO, Accesibilidad y Best
Practices (móvil); LCP < 2,5 s en 4G; CLS 0; JSON-LD válido en Rich Results Test;
CTA "Crear cuenta" visible sin scroll en móvil y escritorio.

## 2. Alcance

**Incluye**
- Landing en Next.js en `/` (grupo de rutas `(marketing)`), con demo animada,
  capturas reales, precios desde el CMS, FAQ, sección de árbitros, WhatsApp.
- Mover el dashboard del panel de `/` a `/dashboard`.
- Endpoint público `GET /api/v1/public/landing` en Laravel.
- SEO/GEO: metadata, Open Graph, JSON-LD, `robots.txt`, `sitemap.xml`, `llms.txt`.
- Retirar la landing Blade, su ruta y el switch por cookie en nginx.
- Tests: unitarios (Vitest), e2e (Playwright) y feature test Laravel; entrada en CI.
- Despliegue al servidor actual.

**No incluye**
- Migración del droplet a otra cuenta de DigitalOcean (spec aparte).
- Cambio de dominio a `facturon.ec`, certificados, redirects (spec aparte). La landing
  toma el dominio de `NEXT_PUBLIC_APP_URL`, así que el cambio será solo configuración.
- Renombrar la marca dentro del panel ("AmePhia Facturación" en títulos del panel).
- Analítica de terceros (GA/Meta Pixel). No se agrega nada por ahora.
- Página propia para árbitros (por ahora es una sección con CTA a WhatsApp).
- Testimonios o contadores de uso: producción tiene 13 documentos y 28 tenants; no se
  inventan cifras ni reseñas.

## 3. Decisiones

| Decisión | Elección | Por qué |
|---|---|---|
| Stack | Next.js 16 (app existente en `frontend/`) | Elección del usuario: motion en React, `next/image`, Metadata API, un solo stack de UI. |
| Dirección visual | Híbrida: hero navy + cuerpo claro azul | Máximo impacto sin romper la identidad del panel y la app. |
| Datos de precios | API pública + ISR 300 s | Los planes y textos siguen editables desde Filament sin redeploy. |
| Dark mode | Ninguno en la landing | El híbrido es el diseño; `next-themes` no debe alterarla. |
| Motion | Paquete `motion` (única dependencia nueva) | Reveals y máquina de estados de la demo; ~30 KB. |
| FAQ | `<details>` nativo | Accesible, indexable, sin JS. |
| Contacto | WhatsApp `+1 334 732 4056` + `info@amephia.com` | Dado por el usuario; `facturon.ec` no tiene correo. |
| Tiendas | Badges configurables por env; vacío → "Próximamente" | Android publicada pero sin URL pública verificada; iOS en revisión. |
| Prueba gratis | No hay (`trial_days = 0` en los 4 planes) | El CTA dice "Crear cuenta", nunca "gratis". Si se activa un trial en Filament, se ajusta el copy. |
| Claims SRI | "Comprobantes autorizados por el SRI", nunca "software autorizado por el SRI" | El SRI autoriza comprobantes, no proveedores. Disclaimer en footer. |

## 4. Estructura y contenido

Una sola página. Anclas: `#funcionalidades`, `#como-funciona`, `#precios`, `#arbitros`, `#faq`.
Todo el copy en español con tildes.

### 4.0 Nav (sticky)
Logo Facturón (glifo SVG + wordmark) → `/`. Links: Funcionalidades, Cómo funciona,
Precios, FAQ. Derecha: "Ingresar" (`/login`) y CTA "Crear cuenta" (`/register`). Si hay
sesión (`getSession()` en servidor): un solo botón "Ir a mi panel" (`/dashboard`).
Transparente sobre el hero; al hacer scroll, fondo navy/blur y borde sutil. Móvil: menú
en sheet con los mismos links.

### 4.1 Hero (navy)
- Eyebrow: "Facturación electrónica · Ecuador".
- H1: **"Facturación electrónica que el SRI autoriza en segundos"**.
- Sub: "Desde $2.99 al mes, sin comisión por documento. Facturas, retenciones, guías y
  más, firmadas con tu certificado y enviadas a tus clientes automáticamente."
  El precio mínimo se toma del plan más barato de la API (no se hardcodea).
- CTAs: "Crear cuenta" (primario, azul) y "Ver cómo funciona" (secundario, ancla a
  `#como-funciona`). Debajo: badges Google Play / App Store (ver §11).
- Derecha (en móvil, debajo): **demo "Emisión en vivo"** (§5).
- Franja de confianza técnica (fila de chips): "Ficha técnica SRI" · "Firma XAdES-BES" ·
  "Certificados BCE, Security Data y ANF" · "Ambiente de pruebas y producción" ·
  "Todas las tarifas de IVA del catálogo SRI (15 %, 8 %, 5 %, 0 %, exento, no objeto)".

### 4.2 Todo lo que emites
Grid de 6 tarjetas con código y nombre SRI: 01 Factura · 03 Liquidación de compra ·
04 Nota de crédito · 05 Nota de débito · 06 Guía de remisión · 07 Comprobante de
retención. Una línea de uso por tarjeta.

### 4.3 Producto real (claro)
Tres filas alternadas texto/imagen con capturas reales:
1. **"Todo tu negocio en un panel"** — captura del dashboard web. Bullets: estado SRI de
   cada documento en vivo, cobros y pendientes, varias empresas desde una cuenta.
2. **"Una factura en 30 segundos"** — captura de "Nueva factura". Bullets: cliente por
   RUC o cédula con datos del SRI, IVA calculado solo, firma y envío automáticos.
3. **"Vende desde el celular o en caja"** — captura de la app móvil en marco de teléfono
   + captura del POS. Bullets: app Android/iOS, sesiones de caja, impresora térmica.

### 4.4 Funcionalidades (`#funcionalidades`)
Bento grid, 12 ítems, icono + título + una frase: Firmamos por ti (tu `.p12`) ·
Autorización con reintentos si el SRI cae · RIDE + XML por correo · POS con caja e
impresora térmica · Inventario y compras · Contabilidad, ATS y declaración de IVA ·
Portal de clientes · Multi-empresa · API REST · App móvil · Proformas y recurrentes ·
Categorización con IA. Los dos primeros ocupan celdas dobles.

### 4.5 Cómo funciona (`#como-funciona`)
Tres pasos numerados: 1) Crea tu cuenta con tu RUC · 2) Sube tu certificado `.p12` ·
3) Emite: se firma, se envía al SRI y llega a tu cliente. Cierre: "En 5 minutos estás
facturando."

### 4.6 Precios (`#precios`)
Datos de la API (§8): eyebrow, título, subtítulo, badge y nota al pie del CMS; tarjetas
por plan con toggle mensual/anual (ahorro % del plan), "Más popular" en `is_featured`,
lista de `features_list`, botón "Crear cuenta" → `/register?plan={slug}`. Copy fijo bajo
el toggle: "Sin comisión por documento. Pago por transferencia bancaria; tu cuenta se
activa al confirmarlo." Si la API falla, la sección no se renderiza.

### 4.7 Facturón para Árbitros (`#arbitros`, navy)
Título: "Una factura por partido, sin perder ninguno." Bullets: partidos pendientes por
facturar en tiempo real · facturación en lote, una factura por partido a la FEF con el
concepto exigido · control de la ventana de recepción (del 1 al 20 de cada mes) ·
reversión automática al anular. CTA: "Escríbenos por WhatsApp" (link `wa.me` con texto
prellenado "Hola, soy árbitro y quiero información de Facturón").

### 4.8 Por qué Facturón
Tres tarjetas: **Hecho para el SRI desde cero** (XML según ficha técnica, XAdES-BES,
web services del SRI) · **Todo en uno** (facturación, POS, inventario, contabilidad,
portal) · **Sin trabajo manual** (firma, envío y correo automáticos). Cuarta línea
destacada: "Precio honesto: desde $2.99 al mes, sin comisión por documento."

### 4.9 FAQ (`#faq`)
`<details>` nativos, primera abierta. Preguntas y hechos que debe contener cada respuesta:
1. ¿Qué necesito para facturar electrónicamente en Ecuador? — RUC activo; firma
   electrónica `.p12` (BCE, Security Data, ANF u otra entidad acreditada); habilitación
   de comprobantes electrónicos en SRI en línea. Facturón guía el proceso.
2. ¿Los comprobantes emitidos con Facturón son válidos ante el SRI? — Sí: XML según la
   ficha técnica, firma XAdES-BES, envío a los web services del SRI, que devuelve la
   autorización. Facturón no es el SRI ni está afiliado a él.
3. ¿Cuánto cuesta? — Planes desde $2.99/mes; sin comisión por documento; anual con
   descuento; ver tabla.
4. ¿Cómo funciona el registro y el pago? — Registro en 2 minutos, eliges plan, pagas
   por transferencia, se activa al confirmar el pago. No hay prueba gratis hoy.
5. ¿Puedo migrar desde otro sistema? — Importa clientes y productos (CSV/Excel); los
   secuenciales continúan donde los dejaste.
6. ¿Qué pasa si el SRI está caído? — El documento queda en cola con reintentos
   automáticos; ves su estado en tiempo real.
7. ¿Puedo facturar desde el celular? — App Android (Google Play) e iOS (en revisión);
   la web también funciona en el móvil.
8. ¿Genera el ATS y la declaración de IVA? — ATS mensual en XML listo para el SRI y
   resumen para la declaración de IVA.
9. ¿Sirve para mi contador? — Acceso para el contador, plan de cuentas, asientos
   automáticos y reportes.
10. ¿Tiene API? — REST documentada; disponible según plan.
11. ¿Puedo manejar varias empresas (RUC)? — Sí, según el plan; cambias de empresa con un clic.
12. ¿Venden certificados de firma? — No; usas el tuyo. No instalas nada.
13. ¿Qué es Facturón para Árbitros? — Módulo para árbitros de fútbol: partidos
    pendientes, una factura por partido a la FEF, control de ventana de recepción.
Cierre: "¿Otra pregunta? Escríbenos por WhatsApp o a info@amephia.com."

### 4.10 CTA final (navy)
"Empieza a facturar hoy." Sub: "Crea tu cuenta en 2 minutos. Sin contratos. Desde
$2.99 al mes." Botones: "Crear cuenta" y "Hablar por WhatsApp".

### 4.11 Footer
Columnas: **Producto** (Funcionalidades, Precios, FAQ, App Android, App iOS) ·
**Cuenta** (Ingresar, Crear cuenta) · **Legal** (Términos `/terms`, Privacidad
`/privacy`, Eliminación de cuenta `/delete-account`) · **Contacto** (WhatsApp,
`info@amephia.com`, AmePhia Systems). Línea final: "© {año} AmePhia Systems Inc. ·
Hecho en Ecuador 🇪🇨". Disclaimer: "Facturón es un producto de AmePhia Systems Inc. No
está afiliado al SRI; la autorización de cada comprobante la emite el SRI."

### 4.12 Botón flotante de WhatsApp
Esquina inferior derecha, `https://wa.me/{NEXT_PUBLIC_WHATSAPP}?text=` con "Hola, quiero
información sobre Facturón". `aria-label="Escribir por WhatsApp"`.

## 5. Demo "Emisión en vivo"

Componente `LiveDemo` dentro de un marco de navegador. Máquina de estados pura
(`demo-machine.ts`: `next(state)` + tabla de duraciones) y un hook `useDemoSequence`
que avanza con temporizadores. Bucle de ~12,5 s:

| Paso | Duración | Qué se ve |
|---|---|---|
| `draft` | 2,5 s | Se arma la factura 001-001-000000123: cliente "Comercial Andina S.A." (RUC 1790012345001), líneas "Servicio de consultoría" $120.00 y "Licencia mensual" $30.00, subtotal $150.00, IVA 15 % $22.50, total $172.50. Las líneas aparecen escalonadas. |
| `signing` | 1,8 s | "Firmando con tu certificado .p12 · XAdES-BES" con barra de progreso. |
| `sending` | 2,2 s | "Enviando al SRI" → check "RECIBIDA" → "Consultando autorización…". |
| `authorized` | 4,0 s | Sello **AUTORIZADO** (emerald), clave de acceso de 49 dígitos tecleándose en mono, fecha y hora de autorización, "Ambiente: Producción". |
| `delivered` | 2,0 s | "RIDE (PDF) + XML enviados a facturacion@comercialandina.ec". Fundido y reinicio. |

- La clave de acceso se genera con `claveAcceso()` en TS: formato real de 49 dígitos
  (fecha ddmmaaaa, tipo 01, RUC, ambiente 2, serie 001001, secuencial, código numérico
  de 8, tipo de emisión 1 y dígito verificador módulo 11). Función pura, con tests.
- `prefers-reduced-motion`: se renderiza `authorized` fijo, sin temporizadores.
- Fuera del viewport (`useInView`): los temporizadores se pausan.
- Es decorativa: `aria-hidden="true"` y un párrafo visualmente oculto que describe el
  flujo para lectores de pantalla.

## 6. Sistema visual

- **Paleta.** Navy `#0B1220` (hero, árbitros, CTA final); superficies oscuras `#101B31`
  y `#162037`; azul `#2B54E4`, hover `#2446C4`; cuerpo blanco y `slate-50`; texto
  `slate-900` / `slate-600`; bordes `slate-200`. Degradado bandera
  `#FFCE00 → #0653C6 → #EF3340` solo en: línea bajo el logo, divisor del hero, borde del
  glifo. `emerald-500` exclusivo para "AUTORIZADO" y checks de éxito.
- **Tipografía** con `next/font/google` (self-hosted en build, `display: swap`):
  Bricolage Grotesque 700/800 (`--font-display`, titulares), Geist (texto, ya cargada
  en el root layout), Geist Mono (clave de acceso, secuenciales, precios).
- **Componentes** en `src/components/marketing/` con colores explícitos (sin `dark:` ni
  tokens semánticos `bg-background` etc.), porque `next-themes` corre con
  `defaultTheme="system"`.
- **Motion** con `motion`: `Reveal` (fade + 12 px, 400 ms, una sola vez, `useInView`
  con margen −80 px), hover en tarjetas (elevación 2 px), nav con blur al scroll.
  `useReducedMotion` desactiva reveals y hovers animados.
- **Imágenes** con `next/image`: capturas del panel a 1440×900 @2x (`panel-dashboard`,
  `panel-invoice`, `panel-documents`), móvil 1080×2400 (`app-home`, `app-create`,
  `app-pos`), todas en `public/marketing/`. `sizes` reales por breakpoint; `priority`
  solo en la primera captura; `width`/`height` fijos. Marcos `BrowserFrame` y
  `PhoneFrame` en CSS.
- **Responsive.** Mobile-first. Hero: copy arriba, demo debajo con alto fijo (evita
  CLS). Bento: 1 col < 640 px, 2 col < 1024 px, 4 col ≥ 1024 px. Precios: carrusel
  horizontal con snap en móvil, 4 columnas en escritorio.
- **Accesibilidad.** Contraste AA, `focus-visible` visible en navy y en claro, skip
  link "Ir al contenido", landmarks (`header`, `main`, `nav`, `footer`), un solo `h1`,
  `h2` por sección con `aria-labelledby`.

## 7. Arquitectura frontend

```
frontend/src/app/(marketing)/layout.tsx      # fuente display, skip link, WhatsApp, sin shell del panel
frontend/src/app/(marketing)/page.tsx        # landing: carga datos (ISR 300 s) y compone secciones + JSON-LD
frontend/src/app/(panel)/dashboard/page.tsx  # ← movido desde (panel)/page.tsx
frontend/src/app/robots.ts
frontend/src/app/sitemap.ts
frontend/src/app/llms.txt/route.ts           # ISR 3600 s, construido desde content + planes
frontend/src/content/landing.ts              # FAQ, comprobantes, features, pasos, razones, árbitros
frontend/src/lib/landing/data.ts             # getLandingData(): API o fixture (LANDING_DATA_SOURCE)
frontend/src/lib/landing/fixture.ts          # datos de ejemplo para tests y desarrollo sin backend
frontend/src/lib/landing/jsonld.ts           # buildJsonLd(data, content, baseUrl)
frontend/src/lib/landing/pricing.ts          # formatPrice, yearlySavingsPercent, cheapestPlan
frontend/src/lib/landing/clave-acceso.ts     # claveAcceso(), modulo11()
frontend/src/lib/landing/demo-machine.ts     # estados, duraciones, next()
frontend/src/lib/landing/config.ts           # WHATSAPP, CONTACT_EMAIL, STORE_PLAY_URL, STORE_APPSTORE_URL, APP_URL
frontend/src/components/marketing/*.tsx      # nav, nav-client, hero, live-demo, trust-strip, documents,
                                             # showcase, features, how-it-works, pricing, arbitros, why,
                                             # faq, cta, footer, whatsapp-button, store-badges, reveal, browser-frame, phone-frame
frontend/public/marketing/                   # capturas, og.png, logo-facturon.svg, icon-facturon.svg
frontend/scripts/capture-screenshots.ts      # Playwright: login demo local + capturas del panel
frontend/scripts/og/og.html + build-og.ts    # genera public/marketing/og.png (1200×630)
```

- **Referencias al dashboard** que cambian de `/` a `/dashboard`: `(auth)/actions.ts`
  (2 `redirect`), `onboarding/onboarding-wizard.tsx` (2 `router.push`),
  `components/panel/sidebar-nav.ts`. El logo de `(auth)/layout.tsx` sigue apuntando a `/`
  (la landing).
- **Carga de datos.** `page.tsx` es server component; `getLandingData()` hace
  `fetch(`${LARAVEL_API_URL}/api/v1/public/landing`, { next: { revalidate: 300 } })`.
  En error devuelve `null` y la sección de precios no se renderiza (el resto sí). Con
  `LANDING_DATA_SOURCE=fixture` devuelve `fixture.ts` (tests e2e y desarrollo sin
  backend).
- **Sesión en nav.** `MarketingNav` (server) llama a `getSession()` de
  `@/lib/auth/session` y pasa `isAuthenticated` a `NavClient` (menú móvil, scroll).
- **Sin proveedores extra.** La landing no usa React Query ni next-themes; el root
  layout sigue envolviendo con `Providers` (ligero) sin cambios.
- **Fuentes.** Bricolage Grotesque se carga solo en `(marketing)/layout.tsx` para no
  pesar en el panel.

## 8. Backend: endpoint público

`GET /api/v1/public/landing` — sin auth, `throttle:60,1`, respuesta cacheada 300 s
(`Cache::remember('landing:public', 300)`), invalidada al guardar un plan o los textos
de precios en Filament (`Cache::forget` en los observers/`afterSave` correspondientes).

```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": 2, "name": "Negocio", "slug": "negocio",
        "description": "Ideal para PyMEs…",
        "price_monthly": 7.99, "price_yearly": 79.90, "currency": "USD",
        "is_featured": true, "yearly_savings_percent": 17,
        "features_list": ["50 documentos/mes", "10 usuarios", "…"]
      }
    ],
    "pricing_content": {
      "eyebrow": "Planes", "title": "…", "subtitle": "…",
      "badge_enabled": true, "badge_text": "…", "footer_note": "…"
    }
  }
}
```

- `plans`: `Plan::active()->where('price_monthly','>',0)->ordered()`, `features_list`
  vía `getFeaturesList()`, `yearly_savings_percent` vía `getYearlySavingsPercent()`.
- `pricing_content`: `PricingContentSettings::all()`; ante excepción, los `default` de
  `definitions()` (misma degradación que hoy en la ruta `/`).
- Controlador `App\Http\Controllers\Api\V1\PublicLandingController@show`.

## 9. SEO / GEO

- **Metadata** (`(marketing)/layout.tsx` + `page.tsx`): `metadataBase` =
  `NEXT_PUBLIC_APP_URL`; title "Facturón — Facturación electrónica del Ecuador
  autorizada por el SRI"; description (≤ 155 caracteres) con "facturación electrónica
  Ecuador", "SRI", "desde $2.99"; `alternates.canonical: "/"`,
  `alternates.languages: { "es-EC": "/" }`; Open Graph y Twitter `summary_large_image`
  con `public/marketing/og.png`; `robots: { index: true, follow: true }`.
- **JSON-LD** (un `<script type="application/ld+json">` con `@graph`):
  - `Organization`: AmePhia Systems Inc., `url` https://amephia.com, `logo`,
    `contactPoint` (email `info@amephia.com`, teléfono `+1-334-732-4056`, `contactType`
    "sales", `areaServed` "EC", `availableLanguage` "es").
  - `SoftwareApplication`: name "Facturón", `alternateName` "Facturón EC",
    `applicationCategory` "BusinessApplication", `operatingSystem` "Web, Android, iOS",
    `url`, `description`, `inLanguage` "es-EC", `countriesSupported` "EC",
    `featureList` (desde `landing.ts`), `offers` = un `Offer` por plan (`price`
    mensual, `priceCurrency` USD, `url` `/register?plan=slug`) + `AggregateOffer`
    (`lowPrice`, `highPrice`), `publisher` → la Organization. **Sin** `aggregateRating`.
  - `FAQPage`: `mainEntity` = las 13 preguntas de `landing.ts`.
- **`robots.ts`**: `allow: "/"` para `*` y explícitamente para `GPTBot`, `ClaudeBot`,
  `Claude-Web`, `PerplexityBot`, `Google-Extended`, `Bingbot`; `disallow` de rutas
  privadas (`/api/`, `/dashboard`, `/onboarding`, `/settings` y el resto de rutas del
  panel, tomadas de una constante `PANEL_PATHS`); `sitemap` absoluto.
- **`sitemap.ts`**: `/`, `/register`, `/login`, `/terms`, `/privacy` con `lastModified`.
- **`llms.txt`** (route handler, ISR 3600 s): formato llmstxt.org — `# Facturón`, cita
  resumen, secciones "Qué es", "Para quién", "Comprobantes", "Precios" (planes vivos),
  "Requisitos", "Enlaces". Solo hechos; sin instrucciones dirigidas a modelos.
- **OG image**: `public/marketing/og.png` 1200×630 generada por `scripts/og/build-og.ts`
  (Playwright captura `og.html`), con logo, H1 y "desde $2.99/mes".
- **HTML**: un `h1`, `h2` por sección, texto real (nada de texto en imágenes), `alt`
  descriptivos en capturas.
- Se **elimina** `backend/public/robots.txt` (nginx sirve archivos existentes en
  `backend/public` antes de pasar al proxy y taparía el de Next). Verificar que no haya
  `favicon.ico`/`sitemap.xml` en `backend/public` con el mismo efecto.

## 10. Cambios en nginx y Laravel

- `docker/nginx/conf.d/production.conf`: eliminar el bloque `location = /` (switch por
  `$cookie_factura_session`); `/` cae en `location /` → `try_files $uri @frontend`.
  Actualizar el comentario de cabecera. Requiere `up -d --force-recreate nginx` (bind
  mount de archivo único; ver memoria de despliegue).
- `backend/routes/web.php`: eliminar la ruta `/` (closure con `$plans`/`$pricingContent`).
- Eliminar `backend/resources/views/welcome.blade.php`. `PricingContentSettings` se
  conserva (lo usan Filament y el endpoint nuevo). `/terms`, `/privacy` y
  `/delete-account` siguen en Laravel.
- `backend/tests/Feature/ExampleTest.php` (pega a `/`): reemplazar por el feature test
  del endpoint público.

## 11. Configuración

Variables `NEXT_PUBLIC_*` (build-time; defaults en `config.ts`, sobreescribibles):

| Variable | Default | Uso |
|---|---|---|
| `NEXT_PUBLIC_WHATSAPP` | `13347324056` | Solo dígitos, para `wa.me`. Se muestra formateado "+1 334 732 4056". |
| `NEXT_PUBLIC_CONTACT_EMAIL` | `info@amephia.com` | Footer, FAQ, JSON-LD. |
| `NEXT_PUBLIC_STORE_PLAY_URL` | vacío | Badge Google Play; vacío → badge gris "Próximamente". |
| `NEXT_PUBLIC_STORE_APPSTORE_URL` | vacío | Badge App Store; vacío → "Próximamente". |
| `NEXT_PUBLIC_APP_URL` | (existente) | `metadataBase`, canonical, sitemap, JSON-LD. |

Variable de servidor: `LANDING_DATA_SOURCE` = `api` (default) | `fixture`.

`frontend/Dockerfile` y `docker/docker-compose.production.yml` reciben las cuatro nuevas
como `ARG`/`args` (`${LANDING_WHATSAPP:-13347324056}`, `${LANDING_CONTACT_EMAIL:-info@amephia.com}`,
`${STORE_PLAY_URL:-}`, `${STORE_APPSTORE_URL:-}`), leídas del `.env` del compose.
`frontend/.env.local` y `.env.example` documentan las variables.

## 12. Pruebas (TDD)

- **Backend** (`tests/Feature/PublicLandingTest.php`): 200 sin auth; solo planes
  activos con precio > 0 y en orden; `features_list` y `yearly_savings_percent`
  presentes; `pricing_content` con las 6 claves; respuesta cacheada (segunda llamada no
  consulta la BD); rate limit responde 429.
- **Frontend unit** (Vitest + Testing Library + jsdom, `pnpm test`):
  `claveAcceso()` (49 dígitos, módulo 11 correcto contra vectores conocidos),
  `demo-machine` (secuencia y bucle), `pricing.ts` (formato, ahorro, plan más barato),
  `jsonld.ts` (estructura `@graph`, offers = planes, FAQ = 13), `config.ts`
  (defaults y parseo del WhatsApp a dígitos), `FAQ` (renderiza `<details>`, primera abierta),
  `StoreBadges` (con y sin URL).
- **Frontend e2e** (Playwright `@playwright/test`, `pnpm test:e2e`, `webServer` =
  `next build && next start` con `LANDING_DATA_SOURCE=fixture`): un `h1` con el texto
  esperado; precios del fixture renderizados y toggle anual cambia montos; `/register?plan=`
  en los botones; JSON-LD parseable con `SoftwareApplication` y `FAQPage`;
  `/robots.txt`, `/sitemap.xml`, `/llms.txt` responden 200 con contenido esperado;
  sin errores de consola; viewport 375 px sin scroll horizontal y CTA visible;
  `prefers-reduced-motion` muestra "AUTORIZADO" de inmediato.
- **CI** (`.github/workflows/ci.yml`): job frontend ejecuta `pnpm lint`, `pnpm test`,
  `pnpm build`; e2e en CI solo si el tiempo lo permite (< 3 min), si no, se corre local
  antes de desplegar.
- **Manual antes de desplegar**: Lighthouse móvil ≥ 90 ×4; Rich Results Test sin
  errores; revisión visual en 375, 768, 1440 px.

## 13. Rendimiento y calidad

LCP < 2,5 s (4G), CLS 0, INP < 200 ms; JS propio de la landing < 120 KB gz (`motion`
incluido); imágenes ≤ 200 KB cada una servidas como AVIF/WebP; fuentes con `swap` y
subsets `latin`; sin peticiones a terceros (fuentes self-hosted, sin analítica).
Verificar `sharp` en la imagen Alpine (`next/image` en producción); si falla el build,
pre-generar WebP y usar `unoptimized` con `srcSet` manual.

## 14. Despliegue

1. Backend: `git pull` + `./deploy.sh update` (endpoint público, ruta `/` retirada).
2. Frontend: `docker compose -f docker/docker-compose.production.yml build frontend`
   → `up -d frontend` (ver memoria: los deploys de frontend no los cubre `deploy.sh`).
3. nginx: `up -d --force-recreate nginx` (nuevo conf sin `location = /`).
4. Verificar: `curl -sI https://<dominio>/` 200 con HTML de la landing; `/dashboard`
   redirige a `/login` sin sesión; `/robots.txt`, `/sitemap.xml`, `/llms.txt`;
   `/api/v1/public/landing` 200; login → `/dashboard`.
5. Rollback: restaurar el conf de nginx anterior + `up -d` de la imagen previa del
   frontend (mantener la imagen anterior etiquetada antes de construir).

## 15. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Dos páginas en `/` (landing y dashboard) rompen el build | Mover el dashboard a `/dashboard` en el mismo commit; e2e cubre login → `/dashboard`. |
| Links externos o correos que apuntaban a `/` como panel | `/` muestra la landing con "Ir a mi panel"; nada queda roto. |
| `sharp` no compila en Alpine | Plan B de §13. |
| `next-themes` oscurece la landing | Colores explícitos, sin tokens semánticos; e2e con `prefers-color-scheme: dark`. |
| Backend caído al construir/servir | ISR con `revalidate`; la página se sirve sin precios en vez de fallar. |
| `backend/public/robots.txt` tapa el de Next | Eliminarlo; verificación en el checklist de despliegue. |
| Copy con afirmaciones no verificables | Sin cifras de uso ni testimonios; claims SRI redactados como en §3. |

## 16. Trabajo posterior (fuera de este spec)

- Migración del droplet a la otra cuenta de DigitalOcean (snapshot transfer).
- Cutover a `facturon.ec`: DNS, certificado (`facturon.ec`, `www`, dominio viejo),
  `APP_URL`/`FRONTEND_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, CORS,
  redirects web (la API sigue en el dominio viejo por la app móvil publicada),
  arreglar la renovación de certbot (hoy `standalone`, falla con nginx en el puerto 80).
- Poner las URLs de tiendas en el `.env` del compose cuando estén públicas.
- Página dedicada para árbitros y página de contadores si el tráfico lo justifica.
