# API de integración de Facturón — diseño

**Fecha:** 2026-09-13 · **Estado:** aprobado por el usuario ("Implementar lo otro de APIs") · **Plan:** `docs/superpowers/plans/2026-09-13-integration-api.md`

## 1. Objetivo

Que un sistema externo del cliente (ERP, tienda en línea, POS, hoja de cálculo con scripts) pueda **enviar a facturar** y **consultar lo facturado** en Facturón mediante una API con llaves por empresa (tenant), **completa según el plan contratado**: solo los planes con `has_api_access` (Negocio, Profesional, Enterprise) pueden crear llaves; cada llave tiene alcances (scopes), límite de peticiones por minuto y caducidad opcional.

Hoy existe un grupo `/api/v1/ext/*` con middleware `VerifyApiKey` que **nunca funcionó**: consulta columnas `tenants.api_key/api_key_enabled/api_last_used_at` que ninguna migración crea, así que toda petición termina en error SQL. Sí existen la tabla `api_keys` (hash sha256, prefijo `fec_`, permisos JSON, `rate_limit_per_minute`, `expires_at`, `is_active`) y el modelo `App\Models\Tenant\ApiKey`, sin CRUD, sin UI y sin documentación. No hay ninguna integración viva que romper.

## 2. Alcance

**Incluido (fase 1):**
- Autenticación por API key contra `api_keys`, gate por suscripción activa + `hasFeature('api_access')`, scopes, rate limit por plan/llave, registro de último uso.
- Gestión de llaves desde el panel (crear, ver una sola vez, rotar, desactivar, eliminar) con página **Configuración → API e integraciones** (upsell si el plan no incluye API).
- Endpoints externos `/api/v1/ext/*`: `me`, `companies`, `documents` (crear+emitir con `Idempotency-Key`, listar, ver, estado, enviar, anular, reenviar correo, RIDE, XML), `customers` (listar, buscar por identificación, crear, ver, actualizar), `products` (listar, ver, crear, actualizar), `catalogs`.
- Documentación pública en `https://facturon.ec/docs/api` + `openapi.yaml` (OpenAPI 3.1) descargable; enlaces desde landing (footer/FAQ), `llms.txt` y sitemap.
- Pruebas backend (auth, scopes, rate limit, idempotencia, flujo documento) y frontend (gate/upsell, typecheck, lint, e2e de la página de docs).

**Fuera de alcance (fase 2):** webhooks de eventos (`webhook_endpoints` ya existe en BD), OAuth2, entorno sandbox propio (el integrador usa el ambiente de pruebas del SRI configurando la empresa en `sri_environment = 1`), SDKs oficiales.

## 3. Decisiones

| Tema | Decisión | Por qué |
|---|---|---|
| Credencial | `Authorization: Bearer fec_…` (preferido) o `X-API-Key: fec_…`. **No** se acepta `?api_key=` en la URL. | Las query strings quedan en logs de proxies/CDN. |
| Almacenamiento | Solo `sha256(key)` + prefijo (`fec_` + 8 chars) para identificarla en la UI; la llave en claro se muestra **una sola vez** al crearla/rotarla. | Igual que Stripe/GitHub; una fuga de BD no expone llaves. |
| Identidad de la petición | El middleware resuelve `ApiKey → Tenant` y fija `request->user()` = **owner del tenant**; guarda la llave en `request->attributes('api_key')`. | Los controladores del panel ya filtran por `user()->tenant_id`; se reutilizan sin duplicar lógica. |
| Gate de plan | `tenant->activeSubscription` **y** `tenant->hasFeature('api_access')` (columna denormalizada `tenants.has_api_access`, sincronizada por `syncPlanLimits()` y revocada por `revokePlanAccess()`). | Coherente con el resto de features y con el fix de suscripciones del 2026-09-13. |
| Scopes | `documents:read`, `documents:write`, `customers:read`, `customers:write`, `products:read`, `products:write`, `catalogs:read` (implícito siempre), `*`. Middleware `api.scope:documents:write`. | Mínimo privilegio; una tienda solo necesita `documents:write` + `customers:write`. |
| Rate limit | Por llave: `min(api_keys.rate_limit_per_minute, límite del plan)`; plan → `enterprise` 300/min, `profesional` 120/min, `negocio` 60/min, otros 30/min. Cabeceras `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`; 429 `rate_limit_exceeded`. | Corrige el slug `empresarial` (inexistente) del middleware actual y permite llaves "de solo lectura lentas". |
| Crear documento | `POST /ext/documents` acepta el **mismo payload** que el panel (`DocumentRequest`) más `send` (bool, default `true`). Con `send=true` valida reglas SRI (`SriPreValidator`) y despacha `ProcessDocumentJob`; responde **201** con el documento en `processing`. La autorización del SRI es asíncrona → el integrador consulta `GET /ext/documents/{id}/status` (o `GET /ext/documents/{id}`). | Reutiliza exactamente el flujo probado del panel; no se bloquea la petición esperando al SRI (puede tardar segundos o entrar en contingencia). |
| Idempotencia | Cabecera opcional `Idempotency-Key` (≤ 128 chars) en `POST /ext/documents`. Se guarda en caché 24 h `{status, body}` por `tenant + key`; una repetición devuelve la **misma respuesta** con `Idempotent-Replayed: true`. Misma llave con otro cuerpo → **409 `idempotency_key_reused`**. Petición concurrente con la misma llave → 409 `idempotency_in_progress`. | Evita facturas duplicadas ante reintentos de red, el problema #1 de integraciones de facturación. |
| Archivos | `GET /ext/documents/{id}/ride` devuelve el **PDF** (binario, `application/pdf`); `GET /ext/documents/{id}/xml` el XML firmado/autorizado (`application/xml`, 404 `xml_not_available` si aún no existe). Con `?url=1` devuelven JSON con una URL temporal de 30 min (misma que usa el panel). | Un integrador quiere el binario directo; la URL temporal sirve para enlazar desde su propio sistema. |
| Envoltorio | Igual que la API del panel: `{ "success": true, "data": …, "message": … }`, paginación `{ data: [], meta: {current_page, last_page, per_page, total} }` (helper `paginated()`), errores `{ success:false, message, error: "codigo_snake", errors?: {...} }`. `per_page` máximo 100. | Un solo estilo de respuesta para toda la plataforma. |
| Llaves por tenant | Máximo 10 activas. Campos nuevos en `api_keys`: `created_by` (FK users, nullable), `last_used_ip`. | Auditoría mínima. |
| Roles | Solo `owner`/`admin` del tenant gestionan llaves (`UserRole`). | Las llaves equivalen a credenciales del owner. |
| Docs | Página Next.js `/docs/api` (grupo marketing, sin auth) generada a partir de `frontend/public/docs/openapi.yaml`, con navegación lateral, ejemplos `curl`/JavaScript/PHP y códigos de error. | El SEO/GEO del sitio ya está montado en Next; la spec es la fuente de verdad para esquemas. |

## 4. Códigos de error (`error`)

| HTTP | `error` | Cuándo |
|---|---|---|
| 401 | `missing_api_key` | Sin cabecera |
| 401 | `invalid_api_key` | Hash no encontrado o llave desactivada |
| 401 | `expired_api_key` | `expires_at` pasado |
| 403 | `tenant_inactive` | Tenant suspendido/cancelado |
| 403 | `subscription_required` | Sin suscripción vigente |
| 403 | `api_access_not_allowed` | Plan sin API (respuesta incluye `upgrade_url`) |
| 403 | `insufficient_scope` | La llave no tiene el scope (respuesta incluye `required_scope`) |
| 403 | `plan_limit_reached` | Límite mensual de documentos |
| 404 | `not_found` | Recurso de otro tenant o inexistente |
| 409 | `idempotency_key_reused` / `idempotency_in_progress` | Ver idempotencia |
| 422 | `validation_error` | Errores de validación (`errors` por campo) o reglas SRI (`errors.sri[]`) |
| 429 | `rate_limit_exceeded` | Con `retry_after` |

## 5. Superficie de la API externa (`/api/v1/ext`)

| Método y ruta | Scope | Notas |
|---|---|---|
| `GET /me` | — | tenant, plan, límites y uso del mes, datos de la llave |
| `GET /companies` | `documents:read` | empresas con establecimientos y puntos de emisión (ids/códigos necesarios para emitir) |
| `GET /documents` | `documents:read` | filtros `status, document_type, company_id, customer_id, date_from, date_to, access_key, search, per_page(≤100), page` |
| `POST /documents` | `documents:write` | payload del panel + `send`; `Idempotency-Key` |
| `GET /documents/{id}` | `documents:read` | detalle con items/cliente/empresa |
| `GET /documents/{id}/status` | `documents:read` | refresca contra el SRI si sigue en proceso |
| `POST /documents/{id}/send` | `documents:write` | borradores/fallidos/rechazados |
| `POST /documents/{id}/void` | `documents:write` | `reason` (marca interna; en Ecuador se anula con nota de crédito) |
| `POST /documents/{id}/email` | `documents:write` | `email` opcional |
| `GET /documents/{id}/ride` | `documents:read` | PDF (o `?url=1`) |
| `GET /documents/{id}/xml` | `documents:read` | XML (o `?url=1`) |
| `GET /customers`, `GET /customers/{id}` | `customers:read` | `search`, `per_page` |
| `GET /customers/lookup?identification=` | `customers:read` | 200 o 404 |
| `POST /customers`, `PATCH /customers/{id}` | `customers:write` | reglas de `CustomerRequest` |
| `GET /products`, `GET /products/{id}` | `products:read` | |
| `POST /products`, `PATCH /products/{id}` | `products:write` | reglas de `ProductRequest` |
| `GET /catalogs/{identification-types,document-types,payment-methods,tax-rates,retention-codes}` | — | |

## 6. Gestión de llaves (`/api/v1/api-keys`, Sanctum, `plan.feature:api_access`, owner/admin)

`GET /` lista; `POST /` `{name, scopes[], expires_in_days?: 30|90|365|null, rate_limit_per_minute?}` → 201 `{api_key, plain_key}`; `PATCH /{id}` `{name?, scopes?, is_active?}`; `POST /{id}/rotate` → nueva `plain_key`; `DELETE /{id}`. Nunca se devuelve el hash; se expone `key_prefix`, `last_used_at`, `last_used_ip`, `expires_at`, `is_active`, `scopes`, `rate_limit_per_minute`, `created_by`.

## 7. Panel

Nueva sección en `settings/page.tsx` → **API e integraciones** ("Llaves de API, límites y documentación") → `/settings/api`. Si `features.has_api_access` es falso: tarjeta de upsell (qué permite la API, planes que la incluyen, botón "Ver planes" → `/settings/subscription`, enlace a docs). Si es verdadero: tabla de llaves, diálogo de creación (nombre, scopes, caducidad), modal "copia tu llave ahora" con `curl` de inicio rápido, acciones rotar/desactivar/eliminar, enlace a `/docs/api`. La sección **Seguridad** deja de mencionar "API keys".

## 8. Seguridad

Hash sha256 + búsqueda por hash (sin comparación de strings); llaves nunca en logs (el middleware no registra la cabecera); solo HTTPS (nginx); `last_used_at` se actualiza con `updateQuietly` como máximo una vez por minuto; los binarios RIDE/XML validan pertenencia al tenant; los errores 404 no distinguen "no existe" de "no es tuyo".

## 9. Pruebas

Backend (SQLite, `CreatesTestTenant` con plan `has_api_access`): `ApiKeyManagementTest` (CRUD, gate de plan, rol, máximo 10, rotación invalida la anterior), `ExternalApiAuthTest` (401/403 por cada código, scopes, rate limit y cabeceras, último uso), `ExternalDocumentsApiTest` (crear+enviar con `Queue::fake`, idempotencia replay/conflicto, listar/ver/estado, RIDE binario, XML 404, anular, límite mensual), `ExternalCatalogApiTest` (clientes lookup/crear/actualizar, productos, catálogos, `me`, `companies`). Frontend: vitest de la página (upsell vs lista, reveal de llave), e2e `/docs/api` 200 + secciones, sitemap incluye `/docs/api`; `pnpm typecheck && pnpm lint`.

## 10. Despliegue

Migración `add_audit_columns_to_api_keys`; sin variables nuevas; `./deploy.sh update` + rebuild del frontend (docs y página del panel). Verificación en producción: `GET /api/v1/ext/me` sin llave → 401 JSON; `/docs/api` 200; `/docs/openapi.yaml` 200; smoke del panel.
