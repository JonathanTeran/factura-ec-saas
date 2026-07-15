# Spec — Vertical "Facturón para Árbitros"

> Estado: **borrador para validar** · Fecha: 2026-07-12 · Autor: Jonathan + Claude
> Decisiones de arranque: **spec primero**, **scraping incluido desde el diseño**.
> Contexto de negocio y estrategia: ver memoria `arbitros-vertical`.

---

## 1. Visión y alcance

Un modo especializado de Facturón para **árbitros de fútbol** (y a futuro comisarios, delegados de control y VAR — ~5 roles por partido). Resuelve que el árbitro **no tiene control de qué partidos ya facturó vs. cuáles le faltan**, cuando debe emitir **1 factura por partido** a la Federación (FEF) dentro de una ventana mensual.

El sistema le da:
1. Sus **partidos pendientes por facturar** en tiempo real.
2. **Facturación en lote**: selecciona N partidos → se emite **1 factura por partido** automáticamente, con el concepto que exige la FEF.
3. **Reversión automática**: si una factura se anula, el partido vuelve a *pendiente*.
4. **Control de la ventana de recepción** de la FEF (avisa si está fuera de fecha).
5. Datos precargados (**campeonatos, clubes, partidos**) vía **scraping de la web pública** de la FEF + ingreso manual como respaldo.

**Fuera de alcance (por ahora):** liquidación de haberes de la FEF, cobros/conciliación de pagos, y otras verticales deportivas (clubes/SAD, asociaciones) que reusarán el mismo patrón de "tipo de negocio".

### Principio de arquitectura
El vertical se implementa como un **módulo especializado ("rama")** sobre el sistema contable principal, **sin alterar el código núcleo**. El núcleo se mantiene como **facturador básico** (simplicidad para el árbitro), pero la estructura permite **escalar a un ERP contable completo** más adelante si el usuario lo requiere, **preservando todo el historial de datos**. Esto es coherente con la arquitectura "core contable + ramas" del sistema y con el patrón de activación por tipo de negocio (una rama por vertical: árbitros, clubes/SAD…).

---

## 2. Activación por tipo de negocio

Al registrarse / en onboarding se pregunta **"¿Qué tipo de negocio eres?"**. Si elige **Árbitro**, se activa el módulo; si no, el facturador queda **exactamente como hoy**.

### Decisión de diseño
Hoy **no existe** un campo de tipo de negocio (confirmado: `Tenant` y `Company` no lo tienen; `Tenant` sí tiene `settings` JSON y feature flags `has_*` vía `hasFeature()` con un `match` hardcodeado).

**Propuesta:** nueva columna `business_type` en `tenants` (enum: `generic` | `referee` | … futuras), añadida a `$fillable`. Es más explícita y consultable que enterrarla en `settings` JSON, y permite gate en backend, navegación y planes.

- Migración: `add_business_type_to_tenants_table` → `string business_type default 'generic'`.
- `Tenant::hasFeature()` o un nuevo `Tenant::isReferee()` para gate.
- Se setea en `OnboardingController` (paso company) y/o en un nuevo paso de onboarding "tipo de negocio" (paso 0 del wizard `onboarding-wizard.tsx`).

---

## 3. Modelo de datos

**Principio clave:** separar el **catálogo público** (compartido, lo mantenemos nosotros/el scraper) de la **participación del árbitro** (por tenant, lo que se factura).

### 3.1 Catálogo público — tablas **globales** (sin `tenant_id`)

Como los catálogos SRI: una sola copia, mantenida por admin/scraper, visible para todos los tenants árbitro.

**`championships`** (campeonatos)
| campo | tipo | nota |
|---|---|---|
| id | pk | |
| name | string | "Formativa Azul 2", "Segunda Categoría", "Liga Pro Serie A" |
| category | string | `formativa` \| `segunda` \| `liga_pro` \| `femenino` \| `copa` … |
| season | string/int | año o temporada "2026" |
| external_ref | string null | id/slug en la web FEF (para scraping/dedupe) |
| invoice_window_start_day | int null | día del mes en que abre la ventana FEF (default global si null) |
| invoice_window_end_day | int null | día en que cierra |
| is_active | bool | |

**`clubs`** (clubes)
| campo | tipo | nota |
|---|---|---|
| id | pk | |
| name | string | **nombre completo oficial** — es el que se imprime tal cual en el concepto de la factura (§5.1) |
| short_name | string null | alias corto solo para UI/búsqueda; NO se usa en el concepto |
| category | string null | categoría(s) donde compite (informativo) |
| external_ref | string null | id en web FEF |
| logo_path | string null | opcional |

> **Cobertura obligatoria:** el catálogo debe incluir el **nombre completo oficial de TODOS los clubes de CUALQUIER categoría** (Liga Pro, Segunda, formativas, femenino, sub-XX, etc.), no solo primera. Un mismo club puede aparecer en varias categorías con el mismo nombre. El árbitro **selecciona** el club (nunca lo escribe a mano), garantizando que el concepto lleve el nombre exacto que la FEF espera. El scraper y las cargas manuales deben normalizar contra este catálogo (dedupe por `external_ref` / nombre oficial) para no crear duplicados con nombres parciales.

**`matches`** (partidos — del feed público)
| campo | tipo | nota |
|---|---|---|
| id | pk | |
| championship_id | fk **NOT NULL** | siempre del catálogo — un partido no existe sin campeonato (§5.5) |
| home_club_id | fk | |
| away_club_id | fk | |
| match_date | date | fecha del partido (base para concepto y ventana) |
| stage | string null | fecha/jornada/ronda |
| external_ref | string null | id del partido en web FEF (dedupe del scraper) |
| officials | json null | lista de oficiales publicados (nombre + rol), si prensa los publicó |
| source | string | `scraper` \| `manual` |
| published_at | timestamp null | cuándo apareció en la web pública |

> Los partidos del scraper son **post-partido** (retraso ~1–2 días). Si un árbitro necesita registrar antes, usa ingreso manual (crea un `matches.source = manual`).

### 3.2 Participación del árbitro — tabla **por tenant** (`BelongsToTenant`)

**`officiated_matches`** (partido pitado por ESTE árbitro — la unidad de "pendiente por facturar")
| campo | tipo | nota |
|---|---|---|
| id | pk | |
| tenant_id | fk | `use BelongsToTenant` |
| match_id | fk null | referencia al catálogo público (si vino de scraping o lo eligió) |
| championship_id | fk **NOT NULL** | obligatorio, siempre del catálogo (§5.5) |
| home_club_id / away_club_id | fk | denormalizado |
| match_date | date | |
| role | string | `arbitro` \| `asistente_1` \| `asistente_2` \| `cuarto` \| `var` \| `avar` \| `comisario` \| `delegado` |
| fee | decimal | valor a facturar (autocompletado del tarifario, editable) |
| status | string | `pending` \| `queued` \| `invoiced` \| `blocked_window` |
| electronic_document_id | fk null | la factura emitida (cuando `invoiced`) |
| invoiced_at | timestamp null | |
| notes | string null | |

**Estados (`status`):**
- `pending` — pendiente por facturar (default).
- `queued` — factura despachada, esperando autorización SRI.
- `invoiced` — factura **autorizada**; `electronic_document_id` apuntando al doc.
- `blocked_window` — intentó facturar fuera de la ventana FEF; queda visible pero no se emite hasta que abra.

**Reversión:** si el `ElectronicDocument` pasa a `VOIDED` o `REJECTED`, un listener vuelve el `officiated_match` a `pending` y limpia `electronic_document_id`.

**`referee_fee_schedules`** (tarifario, opcional para MVP-plus)
`tenant_id, championship_id (null=global), role, fee` → autocompleta `officiated_matches.fee`. MVP puede arrancar con valor manual + un default por rol en `settings`.

### 3.3 La FEF como cliente
La FEF es un **`Customer`** normal del tenant: `identification_type = RUC`, `identification = <RUC FEF>`, `business_name = "Federación Ecuatoriana de Fútbol"`. Se crea automáticamente al activar el módulo árbitro (seed/first-run) y se usa como receptor en todas las facturas del vertical. *(Confirmar RUC receptor: ¿FEF nacional o la asociación provincial correspondiente? — ver §9.)*

---

## 4. Flujos principales

### 4.1 Alta como árbitro
Onboarding → "tipo de negocio: Árbitro" → `tenants.business_type = referee` → se crea el Customer FEF → navegación muestra el grupo **Árbitro**.

### 4.2 Cargar partidos
- **Automático (scraper):** un job programado importa partidos públicos y, cuando la web publica los oficiales, **matchea al árbitro por nombre** y crea/propone `officiated_matches` en estado `pending`. (Ver §6.)
- **Manual (respaldo):** el árbitro pulsa "Registrar partido pitado" → selecciona **campeonato** (precargado) → **club local / visitante** (precargados) → **fecha** → **rol** → **valor** (autocompletado, editable) → guarda como `pending`.

### 4.3 Pendientes por facturar
Pantalla lista todos los `officiated_matches` en `pending` (y `blocked_window`), agrupados por mes/campeonato, con total a cobrar. Selección múltiple (checkbox) + botón **"Facturar seleccionados"**.

### 4.4 Facturación en lote → 1 factura por partido
Al confirmar, por **cada** partido seleccionado:
1. Valida ventana FEF (§5.2). Si está fuera → marca `blocked_window`, no emite, avisa.
2. Construye el **concepto** (§5.1).
3. Crea `ElectronicDocument` (`document_type = 01`, receptor = Customer FEF) en `DRAFT` + un `DocumentItem` (línea libre, `product_id = null`, `description = concepto`, `quantity = 1`, `unit_price = fee`, IVA según corresponda) + `access_key` vía `AccessKeyService`.
4. `ProcessDocumentJob::dispatch($doc)` → firma + envío + autorización asíncronos.
5. `officiated_match.status = queued`, guarda `electronic_document_id`.

Al autorizar (`DocumentAuthorized`), un listener pone `status = invoiced`, `invoiced_at`. Si `REJECTED/FAILED`, vuelve a `pending` con motivo.

> **Reutiliza** el patrón ya probado de `RecurringInvoiceService::generateDocument()` + `ProcessRecurringInvoicesJob`. La emisión en lote puede correr síncrona (respuesta inmediata) o como job `InvoiceOfficiatedMatchesJob` si el lote es grande.

### 4.5 Anulación
El árbitro anula una factura (flujo actual `DocumentController::void`, solo sobre `AUTHORIZED`). Listener → `officiated_match` vuelve a `pending`. *(Nota fiscal: la anulación real en Ecuador suele requerir Nota de Crédito; el `void` interno marca `VOIDED`. Definir si el vertical emite NC automática — ver §9.)*

---

## 5. Reglas de negocio

### 5.1 Concepto automático (requisito FEF)
Cada factura lleva en el **detalle de la línea** el partido, armado como:

```
{club_local} - {club_visitante} del {match_date:%-d de %B de %Y}, campeonato {championship.name}
```
Ejemplo: `Barcelona - Emelec del 14 de julio de 2025, campeonato Formativa Azul 2`

- Plantilla **configurable** (por si la FEF pide otro formato o incluir el rol).
- Localización de fecha en español.

### 5.2 Ventana de recepción FEF
**Confirmado:** la FEF recepta facturas **del 1 al 20 de cada mes** (dato público). Valores por defecto `window_start_day = 1`, `window_end_day = 20`, **configurable** (global + override por campeonato, por si alguna asociación difiere).

> Se bloquea la **emisión de la factura** fuera de ventana, **no** el registro del partido: el partido pitado siempre se registra y queda *pendiente*; solo se impide facturarlo hasta que abra el periodo.

- Los partidos del mes **M** se facturan en la ventana del mes **M+1**, entre `window_start_day` y `window_end_day`.
- Si hoy está **dentro** de la ventana para ese partido → se emite.
- Si está **fuera** (p.ej. partido del 22 que se intenta facturar el mismo mes, o pasado el cierre) → **no se emite**, el partido queda visible como `blocked_window` con el mensaje: *"Pasado la fecha. La FEF recibe del {start} al {end}. Se habilitará el 1° del próximo periodo."*
- Notificación/recordatorio cuando abre la ventana (§7).

### 5.3 Valores
`fee` autocompletado desde tarifario por campeonato+rol (o default por rol); editable por partido. El árbitro conoce sus valores.

### 5.4 IVA / retención
La FEF suele retener → el documento es factura normal (`01`); la retención la emite la FEF, no el árbitro. IVA según el servicio (normalmente actividad no gravada / tarifa según caso) — **confirmar** el `tax_percentage_code` que aplica a servicios arbitrales (§9). El modelo `DocumentItem` ya soporta todas las tarifas (0/5/8/12/13/15).

### 5.5 Campeonato obligatorio (siempre del catálogo)
**Todo partido —manual o del scraper— debe tener un campeonato existente en el catálogo.** No se permite crear un partido con campeonato en texto libre ni con campeonato nulo.
- En el registro manual, el campeonato es un **selector obligatorio** poblado desde `championships`; sin campeonato válido no se puede guardar el partido.
- Si el árbitro necesita un campeonato que **aún no existe**, no lo crea él: lo **solicita** (botón "pedir campeonato") y **nosotros (backoffice)** lo agregamos al catálogo antes de que pueda registrar el partido. Alineado con "si sale un nuevo campeonato, lo registramos en la plataforma".
- El scraper también normaliza contra `championships` (crea/actualiza por `external_ref`); un partido scrapeado sin campeonato identificable queda en cuarentena, no se propone al árbitro.
- El nombre del campeonato del catálogo es el que se concatena en el concepto (§5.1), garantizando consistencia.

---

## 6. Ingesta de datos desde la API pública FEF (incluido en el diseño)

> **Hallazgo (2026-07-14):** la FEF expone una **API pública JSON ya operativa** en `https://apiweb.fef.ec/api/public` (sin auth, `CORS *`, Swagger en `/docs?format=json`, 444 endpoints). No hace falta scrapear HTML ni esperar a una "web v2". Y —clave— **la API sí publica los árbitros de cada partido.** Detalle completo de endpoints en §13.

**El endpoint que resuelve casi todo:** `GET /public/competitions/matches/recent` (opcional `?hierarchy_id=`).
Devuelve, para la ventana de la temporada en curso, **todos los partidos jugados de todos los campeonatos** con estos campos por partido:
`match_id` (uuid, estable), `match_date`, `time`, `tournament` (campeonato), `category`, `stage`, `group`, `matchday`, `stadium`, `home_team`, `away_team`, `result`, y los oficiales: **`center_referee`, `assistant_referee_1`, `assistant_referee_2`, `fourth_official`** + `local_technician`, `visiting_technician`.

**Cobertura medida (1 llamada, 2132 partidos, feb–ago 2026):** árbitro central 89%, asistentes 88–89%, cuarto árbitro 79%. **32 competiciones**, incluyendo justo el mercado objetivo: formativas sub-13/15/17/19, **campeonatos provinciales de las 20 provincias** (segunda categoría), Copa Ecuador, futsal y femenino.

**Esto habilita el "sueño" del producto:** autodetectar los partidos de cada árbitro.

**Componentes:**
- Comando artisan `arbitros:sync-matches` (en `app/Console/Commands/`) registrado en `routes/console.php` con `Schedule::command(...)->hourly()->withoutOverlapping()->onOneServer()` (misma infra; corre en Horizon). Cachea y respeta un intervalo razonable (la data es post-partido, no en vivo).
- **Ingesta:** por cada partido del feed → upsert de `championships` (por `tournament`), `clubs` (por nombre de equipo) y `matches` (por `match_id` = `external_ref`, idempotente). Los nombres de oficiales se guardan en `matches.officials`.
- **Auto-matching del árbitro:** se normaliza el nombre (mayúsculas/acentos, orden apellidos-nombres) y se cruza contra el nombre/cédula del perfil del árbitro. Si coincide con `center_referee`/`assistant_referee_1`/`assistant_referee_2`/`fourth_official`, se crea un `officiated_match` **propuesto** (`pending`, `source=scraper`) con el **rol autodetectado** según en qué campo apareció. El árbitro solo **confirma** (y ajusta valor) → cero tipeo.
- **Fallback manual** siempre disponible (§4.2) para partidos aún no publicados o para roles que la API no trae.

**Límites / consideraciones:**
- La API **no** trae **comisario, delegado de control ni VAR/AVAR** (solo terna + cuarto árbitro + técnicos). Esos roles se registran **manual**. De los "5 por partido", 4 pueden ser automáticos.
- Formato de nombre: `APELLIDO APELLIDO NOMBRE NOMBRE` en mayúsculas → el matching necesita normalización y un **paso de confirmación** del árbitro para resolver homónimos.
- Data **post-partido** (partidos jugados); coincide con el flujo (facturar el periodo anterior). Para históricos por campeonato: `matchdays`/`stages?path=` (sin oficiales).
- Solo data **pública**; sin acceso a la BD de la FEF. La ingesta es **best-effort**: si cambia el esquema, degrada a manual sin romper la facturación.
- La API de la FEF corre con `APP_DEBUG` activo (nos filtra stack traces). Es descuido de ellos; nosotros solo consumimos los endpoints públicos documentados en su Swagger.

---

## 7. Notificaciones

**Gap detectado:** el backend **no tiene push/FCM**, y la app Flutter **no tiene `firebase_messaging`** (solo `flutter_local_notifications`). Hay canales **email** y **WhatsApp** (Twilio) server-side.

**MVP:** recordatorios de "tienes N partidos pendientes por facturar" y "abrió la ventana FEF" vía **WhatsApp/email** (infra existente `NotificationService`), + notificaciones **locales** en la app cuando está en uso.

**Fase posterior (workstream aparte):** push real → añadir `firebase_messaging` en Flutter + almacenar device tokens + canal push en backend. No bloquea el MVP.

---

## 8. Pantallas / UI

### Panel web (Next.js)
Nuevo grupo condicional **"Árbitro"** en `sidebar-nav.ts`. **Requiere** volver `navGroups` dinámico según `tenant.business_type` (hoy es estático; mismo lugar donde ya se ocultan Inventario/POS). Pantallas:
- **Pendientes por facturar** (lista + selección múltiple + total + botón facturar).
- **Registrar partido** (form: campeonato → clubes → fecha → rol → valor).
- **Historial** (facturados, con acceso al RIDE/estado SRI; reusa el detalle de documento actual).
- (Admin nuestro) **Catálogos**: campeonatos/clubes/partidos y tarifario.

### Móvil (Flutter)
El dolor real es móvil (el árbitro factura desde el cel). `AppShell`/`WalletNavBar` tiene 4 pestañas fijas hardcodeadas → **condicionar por tipo de negocio**:
- Reemplazar/añadir una pestaña **"Partidos"** (pendientes) para tenants árbitro, o entrada en el menú "+".
- Pantalla **Pendientes** con multiselección y "Facturar" (emite 1×1).
- Form **Registrar partido** (selectores precargados).

---

## 9. Preguntas abiertas (a confirmar — varias con Max / FEF)

1. ~~**Ventana de recepción exacta**~~ → **CONFIRMADO: del 1 al 20 de cada mes** (§5.2). Queda por confirmar solo si alguna asociación provincial usa una ventana distinta (override por campeonato).
2. **Receptor de la factura**: ¿RUC de la FEF nacional, o de la asociación provincial según el campeonato? ¿Puede variar por campeonato?
3. **IVA de servicios arbitrales**: ¿qué `tax_percentage_code` aplica? (0 / no objeto / 15).
4. **Anulación fiscal**: ¿el vertical debe emitir **Nota de Crédito** automática al anular, o basta el `void` interno?
5. **Cómo reporta hoy el árbitro** (informe/acta al COE, el "SFA"): útil como validación cruzada, pero ya no es imprescindible — la API pública (§6, §13) trae los partidos con árbitro.
6. **Roles a soportar en v1**: la API cubre terna + cuarto árbitro de forma automática; comisario/delegado/VAR van manuales. ¿Arrancamos v1 solo con los 4 automáticos y dejamos los manuales para después?
7. **Tarifario**: ¿los valores por campeonato+rol son estándar y conocidos (para precargar), o cada árbitro los ingresa?
8. ~~**Web pública v2**~~ → **RESUELTO:** la API pública ya está operativa y expone los oficiales por partido (§6, §13). No dependemos de una web v2.

---

## 10. Fases de entrega

| Fase | Contenido | Depende de |
|---|---|---|
| **0 — Fundación** | `business_type` en tenant + pregunta en onboarding + gate + Customer FEF automático + navegación dinámica (web y móvil) | — |
| **1 — Catálogos + registro manual** | Tablas `championships`/`clubs`/`matches`/`officiated_matches` + seeds/admin de catálogos + form "Registrar partido" (web+móvil) | Fase 0 |
| **2 — Pendientes + facturación 1×1** | Pantalla pendientes + emisión en lote (1 factura/partido) + concepto auto + validación ventana + reversión por anulación | Fase 1 |
| **3 — Ingesta API FEF** | Comando `arbitros:sync-matches` + upsert catálogo + auto-matching por nombre + proponer pendientes con rol autodetectado | Fase 1 (API ya operativa, sin dependencia externa) |
| **4 — Notificaciones** | Recordatorios WhatsApp/email de pendientes y apertura de ventana | Fase 2 |
| **5 — Push real (opcional)** | FCM en Flutter + device tokens + canal push backend | — |

> Estrategia: **salir antes que SIFAC** (repositorio web de la FEF). El diferencial es la app móvil con control + recordatorios; priorizar Fases 0–2 para tener algo usable rápido.

---

## 11. Puntos de integración (referencia técnica)

- **Tipo de negocio**: `Tenant` (`backend/app/Models/Tenant/Tenant.php`, `settings` JSON + `hasFeature()`), onboarding `OnboardingController::company()` + `frontend/src/app/onboarding/onboarding-wizard.tsx`.
- **Emisión**: crear `ElectronicDocument` (DRAFT) + `DocumentItem` + `AccessKeyService::generate` + `ProcessDocumentJob::dispatch`. Plantilla: `RecurringInvoiceService::generateDocument()` + `ProcessRecurringInvoicesJob`.
- **Anulación/estado**: `DocumentController::void()`, enum `DocumentStatus`, evento `DocumentAuthorized`.
- **Cliente FEF**: `Customer` (`identification_type=RUC`).
- **Línea libre**: `DocumentItem` con `product_id=null` y `description=concepto`.
- **Multi-tenant**: `use App\Traits\BelongsToTenant;` + columna `tenant_id` (para `officiated_matches`).
- **Scheduler/jobs**: `backend/routes/console.php` (`Schedule::command/job`), Horizon, colas `sri`/`emails`. Comandos en `app/Console/Commands/`.
- **Notificaciones**: `backend/app/Services/Notification/NotificationService.php` (email + WhatsApp). Sin FCM.
- **Navegación**: `frontend/src/components/panel/sidebar-nav.ts` (estático → hacer dinámico); Flutter `mobile/lib/core/widgets/app_scaffold.dart` (`AppShell`/`WalletNavBar`, tabs hardcodeadas) + `create_menu.dart`.

---

## 12. Negocio y costos (resumen)

Suscripción **anual** (posible tiering por documentos). Costos: servidor ~$40/mes (escala por almacenamiento), Android ~$25 único, iOS $100/año. Reparto 50/50; el socio da la cara. Detalle en memoria `arbitros-vertical`.

---

## 13. Fuentes de datos — API pública FEF

Base: `https://apiweb.fef.ec/api/public` · sin autenticación · `Access-Control-Allow-Origin: *` · Laravel/PHP 8.3 · Swagger completo en `https://apiweb.fef.ec/docs?format=json` (444 endpoints). Verificado 2026-07-14.

**Endpoints públicos que usaremos:**

| Endpoint | Params | Devuelve | Uso |
|---|---|---|---|
| `/competitions/matches/recent` | `hierarchy_id?` | Partidos jugados (temporada) con **oficiales** | **Fuente principal**: catálogo + auto-matching de árbitro |
| `/competitions/matches/recent/competitions` | — | Lista de competiciones (name, `hierarchy_id`, `path`) | Enumerar campeonatos |
| `/competitions/matches/matchdays` | `path` | Partidos por jornada (**sin** oficiales) | Históricos por campeonato |
| `/competitions/matches/stages` | `path` | Partidos por etapa (**sin** oficiales) | Históricos |
| `/competitions/teams` | `path` | Equipos/clubes (name, slug, logo) | Catálogo de clubes |
| `/competitions/standings` | `path` | Tabla de posiciones | (secundario) |
| `/competitions/top-scorers` | `path` | Goleadores | (no aplica) |
| `/site-sections`, `/menu-header` | — | Estructura del sitio | Enumerar todos los `path` |

**Campos de un partido en `/matches/recent`:**
`match_id` (uuid estable), `match_date`, `time`, `tournament`, `category`, `stage`, `group`, `matchday`, `stadium`, `home_team`, `away_team`, `result`, `center_referee`, `assistant_referee_1`, `assistant_referee_2`, `fourth_official`, `local_technician`, `visiting_technician`.

**Cobertura de oficiales (muestra 2132 partidos, feb–ago 2026):** central 89% · asistente 1 89% · asistente 2 88% · cuarto árbitro 79%. **No** trae comisario, delegado ni VAR.

**Competiciones en el feed (32):** formativas sub-13/15/17/19, campeonatos provinciales (20 provincias), Copa Ecuador, Super Copa, Superliga Femenina, Ascenso Femenino, ligas de futsal, Liga Desarrollo Conmebol.

> Nota: los endpoints con detalle de partido y edición (`/home-competitions/matches/{id}`, `/football-management/...`) requieren **autenticación** y son del backoffice de la FEF — no los usamos.
