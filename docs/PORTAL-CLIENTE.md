# Portal de Cliente - Documentacion Tecnica

## Descripcion General

El Portal de Cliente permite que los **clientes finales** (quienes reciben facturas) accedan a un portal web para consultar y descargar sus documentos electronicos autorizados por el SRI.

**URL de acceso:** `/portal/login`

### Funcionalidades

- Acceso via **magic link** (enlace magico por email, sin contrasena)
- Dashboard con estadisticas de documentos
- Lista de documentos con filtros (tipo, fecha, busqueda)
- Detalle completo de cada documento (items, totales, info empresa emisora)
- Descarga de RIDE (PDF) y XML autorizado
- Soporte multi-tenant (un cliente puede tener documentos en multiples empresas)

---

## Arquitectura

### Flujo de Autenticacion

```
Cliente ingresa email/cedula/RUC en /portal/login
    |
    v
Sistema busca al cliente en todos los tenants
    |
    ├── No encontrado -> Redirige a "revisa tu email" (no revela existencia)
    ├── 1 tenant -> Genera magic link y envia email
    └── N tenants -> Muestra selector de empresa, luego envia email

Cliente recibe email con enlace magico
    |
    v
Click en enlace -> /portal/auth/{token}
    |
    ├── Token invalido/expirado -> Redirige a login con error
    └── Token valido -> Crea sesion, setea cookie, redirige a /portal/

Navegacion en el portal (cookie de sesion)
    |
    v
Middleware CustomerPortalAuth valida sesion en cada request
    |
    ├── Sesion valida -> Permite acceso, actualiza actividad
    └── Sesion invalida/expirada -> Borra cookie, redirige a login
```

### Aislamiento por Tenant

Los clientes del portal **NO** son usuarios del sistema (modelo `User`). Usan un sistema de autenticacion independiente basado en sesiones propias.

El trait `BelongsToTenant` aplica global scope usando `auth()->user()->tenant_id`. Como los clientes del portal no pasan por `auth()`, todas las queries del portal usan:

```php
// Usar forTenant() o withoutTenantScope() + filtro manual
ElectronicDocument::withoutTenantScope()
    ->where('tenant_id', $session->tenant_id)
    ->whereHas('customer', fn($q) => $q->withoutTenantScope()
        ->where('identification', $session->identification))
```

### Solo Documentos Autorizados

El portal solo muestra documentos con status `AUTHORIZED`. Los clientes finales nunca ven borradores, fallidos o en proceso.

---

## Base de Datos

### Tablas Nuevas

**`customer_portal_tokens`** - Tokens de magic link

| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | bigint PK | Auto-increment |
| tenant_id | FK -> tenants | Tenant al que pertenece |
| email | string | Email del cliente |
| identification | string(20) | Cedula/RUC/Pasaporte |
| token | string(64) UNIQUE | Token aleatorio |
| expires_at | timestamp | Fecha de expiracion (24h por defecto) |
| used_at | timestamp nullable | Cuando fue usado |
| ip_address | string(45) nullable | IP del uso |
| created_at, updated_at | timestamps | - |

**`customer_portal_sessions`** - Sesiones activas del portal

| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | string(40) PK | ID aleatorio |
| tenant_id | FK -> tenants | Tenant de la sesion |
| email | string | Email del cliente |
| identification | string(20) | Identificacion del cliente |
| customer_name | string(300) | Nombre para mostrar |
| ip_address | string(45) nullable | IP del cliente |
| user_agent | text nullable | User agent del navegador |
| last_activity_at | timestamp | Ultima actividad |
| expires_at | timestamp | Expiracion de la sesion (7 dias) |
| created_at, updated_at | timestamps | - |

### Migracion

```bash
php artisan migrate
```

Archivo: `database/migrations/2026_02_17_000000_create_customer_portal_tables.php`

---

## Configuracion

Archivo: `config/portal.php`

| Parametro | Default | Descripcion |
|-----------|---------|-------------|
| `token_expiry_hours` | 24 | Horas de validez del magic link |
| `session_expiry_days` | 7 | Dias maximos de la sesion |
| `session_inactivity_minutes` | 120 | Minutos de inactividad antes de expirar |
| `max_magic_link_requests_per_hour` | 3 | Rate limit por email |
| `cookie_name` | `customer_portal_session` | Nombre de la cookie |
| `documents_per_page` | 15 | Documentos por pagina |
| `show_only_authorized_documents` | true | Solo mostrar autorizados |

Variables de entorno opcionales:
```env
PORTAL_TOKEN_EXPIRY_HOURS=24
PORTAL_SESSION_EXPIRY_DAYS=7
PORTAL_SESSION_INACTIVITY_MINUTES=120
PORTAL_MAX_REQUESTS_PER_HOUR=3
PORTAL_COOKIE_NAME=customer_portal_session
```

---

## Rutas

### Publicas (sin autenticacion)

| Metodo | URL | Nombre | Descripcion |
|--------|-----|--------|-------------|
| GET | `/portal/login` | `portal.login` | Formulario de login |
| POST | `/portal/login` | `portal.login.send` | Enviar magic link |
| GET | `/portal/auth/{token}` | `portal.auth` | Validar token y crear sesion |
| GET | `/portal/link-sent` | `portal.link-sent` | Confirmacion "revisa tu email" |

### Protegidas (requieren sesion de portal)

| Metodo | URL | Nombre | Descripcion |
|--------|-----|--------|-------------|
| GET | `/portal/` | `portal.dashboard` | Dashboard con estadisticas |
| GET | `/portal/documents` | `portal.documents.index` | Lista de documentos |
| GET | `/portal/documents/{id}` | `portal.documents.show` | Detalle de documento |
| GET | `/portal/documents/{id}/ride` | `portal.documents.ride` | Descarga RIDE PDF |
| GET | `/portal/documents/{id}/xml` | `portal.documents.xml` | Descarga XML autorizado |
| POST | `/portal/logout` | `portal.logout` | Cerrar sesion |

Archivo de rutas: `routes/portal.php`

---

## Estructura de Archivos

```
backend/
├── app/
│   ├── Console/Commands/
│   │   └── CleanExpiredPortalSessions.php    # Comando de limpieza
│   ├── Http/
│   │   ├── Controllers/Portal/
│   │   │   └── PortalAuthController.php       # Auth + descargas
│   │   └── Middleware/
│   │       └── CustomerPortalAuth.php         # Middleware de sesion
│   ├── Livewire/Portal/
│   │   ├── PortalDashboard.php                # Dashboard
│   │   ├── PortalDocumentList.php             # Lista de documentos
│   │   └── PortalDocumentShow.php             # Detalle de documento
│   ├── Mail/
│   │   └── DocumentAuthorizedMail.php         # Email post-autorizacion SRI
│   ├── Models/Portal/
│   │   ├── CustomerPortalToken.php            # Modelo de tokens
│   │   └── CustomerPortalSession.php          # Modelo de sesiones
│   ├── Notifications/
│   │   └── CustomerPortalMagicLinkNotification.php  # Email magic link
│   └── Services/Portal/
│       └── CustomerPortalService.php          # Logica de negocio
├── config/
│   └── portal.php                             # Configuracion
├── database/
│   ├── factories/
│   │   └── CustomerPortalTokenFactory.php     # Factory para tests
│   └── migrations/
│       └── 2026_02_17_000000_create_customer_portal_tables.php
├── resources/views/
│   ├── emails/
│   │   └── document-authorized.blade.php      # Template email con adjuntos
│   ├── layouts/
│   │   └── portal.blade.php                   # Layout del portal
│   ├── livewire/portal/
│   │   ├── portal-dashboard.blade.php         # Vista dashboard
│   │   ├── portal-document-list.blade.php     # Vista lista documentos
│   │   └── portal-document-show.blade.php     # Vista detalle documento
│   └── portal/
│       ├── login.blade.php                    # Login form
│       ├── link-sent.blade.php                # Confirmacion envio
│       └── select-tenant.blade.php            # Selector de empresa
├── routes/
│   └── portal.php                             # Rutas del portal
└── tests/
    ├── Feature/Portal/
    │   ├── CustomerPortalAuthTest.php         # Tests de auth
    │   └── CustomerPortalDocumentsTest.php    # Tests de documentos
    └── Unit/Services/
        └── CustomerPortalServiceTest.php      # Tests del servicio
```

---

## Componentes Clave

### CustomerPortalService

Servicio central con toda la logica de negocio del portal.

**Metodos principales:**

| Metodo | Descripcion |
|--------|-------------|
| `findCustomerByEmailOrIdentification($input)` | Busca clientes en todos los tenants |
| `sendMagicLink($tenantId, $email, $identification)` | Genera token y envia email |
| `authenticateWithToken($token, $ip, $userAgent)` | Valida token y crea sesion |
| `getDocumentsForSession($session, $filters, $perPage)` | Documentos paginados con filtros |
| `getDocument($session, $documentId)` | Documento individual (scoped) |
| `getDashboardStats($session)` | Estadisticas del dashboard |
| `cleanupExpired()` | Limpia tokens y sesiones expiradas |

### CustomerPortalAuth (Middleware)

- Lee cookie `customer_portal_session`
- Carga sesion de BD y valida vigencia
- Actualiza `last_activity_at` en cada request
- Pone la sesion en `request()->attributes->set('portal_session', $session)`
- Redirige a login si la sesion es invalida

### DocumentAuthorizedMail

Mailable que envia el email al cliente cuando el SRI autoriza un documento. Incluye:
- Datos del documento (tipo, numero, fecha, totales)
- RIDE PDF y XML como adjuntos
- Enlace al portal de documentos

Se usa en `SendDocumentToClientJob` que se despacha automaticamente desde `ProcessDocumentJob` al autorizar.

---

## Seguridad

| Aspecto | Implementacion |
|---------|----------------|
| Tokens | 64 caracteres aleatorios, un solo uso |
| Expiracion token | 24 horas (configurable) |
| Rate limiting | Max 3 magic links por email por hora |
| Cookie | HttpOnly, SameSite=Lax, Secure en produccion |
| Sesion max | 7 dias desde creacion |
| Inactividad | 2 horas sin actividad = sesion expira |
| Tenant isolation | Filtro explicito `where('tenant_id', ...)` en cada query |
| Enumeracion | No revela si un email/identificacion existe |
| Cleanup | Job diario a las 3 AM limpia registros expirados |

---

## Scheduled Tasks

El comando `portal:cleanup` se ejecuta diariamente a las 3 AM:

```bash
# Ejecutar manualmente
php artisan portal:cleanup
```

Registrado en `routes/console.php`. Limpia tokens expirados con mas de 7 dias y sesiones expiradas.

---

## Tests

```bash
# Ejecutar todos los tests del portal
php artisan test --filter=Portal

# Tests individuales
php artisan test --filter=CustomerPortalAuthTest
php artisan test --filter=CustomerPortalDocumentsTest
php artisan test --filter=CustomerPortalServiceTest
```

### Cobertura de tests

**Auth (10 tests):**
- Login page loads
- Magic link enviado para cliente existente
- Magic link por identificacion
- No error para cliente inexistente
- Token valido crea sesion
- Token expirado redirige a login
- Token usado no se puede reusar
- Logout borra sesion y cookie
- Rate limiting funciona
- Multi-tenant muestra selector

**Documents (10 tests):**
- Dashboard carga con sesion valida
- Dashboard redirige sin sesion
- Solo muestra documentos autorizados
- Documentos de otros tenants no visibles
- Detalle de documento carga
- 404 para documento de otro cliente
- 404 para documento draft
- Sesion expirada redirige a login
- Sesion inactiva redirige a login
- Descargas retornan 404 cuando no hay archivo

**Service (8 tests):**
- Buscar cliente por email
- Buscar cliente por identificacion
- Buscar en multiples tenants
- Enviar magic link
- Tokens previos se invalidan
- Autenticacion con token valido
- Autenticacion con token expirado retorna null
- Dashboard stats

---

## Flujo Completo Post-Autorizacion SRI

```
1. ProcessDocumentJob detecta autorizacion SRI
2. Despacha SendDocumentToClientJob
3. SendDocumentToClientJob usa DocumentAuthorizedMail
4. Email llega al cliente con:
   - Datos del documento
   - RIDE PDF adjunto
   - XML autorizado adjunto
   - Enlace "Ver en Portal" -> /portal/login
5. Cliente hace click en "Ver en Portal"
6. Ingresa su email -> recibe magic link
7. Click en magic link -> accede al portal
8. Ve dashboard, lista de documentos, descarga archivos
```
