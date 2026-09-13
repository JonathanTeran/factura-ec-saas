# Integration API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a working, plan-gated integration API (`/api/v1/ext/*`) with per-tenant API keys managed from the Next.js panel, idempotent document creation + emission, and public docs at `facturon.ec/docs/api`.

**Architecture:** A rewritten `VerifyApiKey` middleware resolves `fec_…` keys against the `api_keys` table, enforces subscription + `has_api_access` + scopes + per-key rate limits, and sets `request->user()` to a tenant owner/admin so the existing panel controllers run unchanged. Thin `Ext\*` controllers extend the panel controllers to add idempotency, create+send, 404 semantics, binary RIDE/XML and `per_page` caps. Key CRUD is a Sanctum endpoint behind `plan.feature:api_access`; the panel page shows an upsell when the backend answers `feature_not_available`. Docs are a static Next.js page plus a hand-written OpenAPI 3.1 file.

**Tech Stack:** Laravel 12 (Sanctum, Pint, PHPUnit/SQLite), Next.js 16 App Router + React 19 + Tailwind v4 + TanStack Query v5 + Vitest 5 + Playwright.

**Spec:** `docs/superpowers/specs/2026-09-13-integration-api-design.md`

## Global Constraints

- Header auth only: `Authorization: Bearer fec_…` or `X-API-Key: fec_…`; never `?api_key=`.
- Plain key format `fec_` + 40 random chars (`ApiKey::generatePlainKey()`); stored as `sha256`; `key_prefix` = first 12 chars (column widened to 16).
- Scopes: `documents:read`, `documents:write`, `customers:read`, `customers:write`, `products:read`, `products:write`, `catalogs:read`, `*`.
- Rate limits: `min(api_keys.rate_limit_per_minute, plan)` with plan map `enterprise=300`, `profesional=120`, `negocio=60`, default `30`.
- Response envelope identical to `ApiController` (`success/message/data`, `paginated()` meta); error bodies carry `error` codes from spec §4.
- `per_page` max 100 on every ext list.
- Copy in Spanish (Ecuador); never say "gratis" or "software autorizado por el SRI".
- Commit only files from this plan (another session has unrelated uncommitted files in `mobile/`, `.github/`, `backend/app/Services/SRI/SRIService.php`, `backend/config/services.php`, `backend/resources/views/pdf/ride/partials/footer.blade.php`, `docker/docker-compose.production.yml`, `docs/DEPLOYMENT.md`, `docs/app-store-listing.md`, `frontend/Dockerfile`).
- Run backend commands from `backend/` (`php artisan test --compact`, `vendor/bin/pint <files>`), frontend from `frontend/` (`pnpm test`, `pnpm typecheck`, `pnpm lint`).

---

### Task 1: Schema + `ApiKey` model hardening

**Files:**
- Create: `backend/database/migrations/2026_09_13_000001_harden_api_keys_table.php`
- Create: `backend/database/migrations/2026_09_13_000002_add_void_columns_to_electronic_documents.php`
- Create: `backend/database/factories/ApiKeyFactory.php`
- Modify: `backend/app/Models/Tenant/ApiKey.php`
- Modify: `backend/app/Models/SRI/ElectronicDocument.php` (add `voided_at`, `void_reason` to `$fillable`, `voided_at` cast)
- Test: `backend/tests/Unit/ApiKeyModelTest.php`

**Interfaces:**
- Produces: `ApiKey::SCOPES` (array label map), `ApiKey::generatePlainKey()`, `ApiKey::hashKey()`, `ApiKey::prefixFrom()`, `ApiKey::findByPlainKey(string): ?ApiKey` (no global scope, active, includes expired so the middleware can say `expired_api_key`), `$key->hasScope(string): bool`, `$key->isValid()`, `$key->planRateLimit(): int`, `$key->effectiveRateLimit(): int`, `$key->touchUsage(?string $ip): void` (throttled 60 s), `$key->rotate(): string` (returns new plain key).

- [ ] **Step 1: Migration widening `key_prefix`, unique hash, audit columns**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key_prefix', 16)->change();
            $table->foreignId('created_by')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            $table->string('last_used_ip', 45)->nullable()->after('last_used_at');
            $table->unique('key_hash');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropUnique(['key_hash']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('last_used_ip');
            $table->string('key_prefix', 10)->change();
        });
    }
};
```

- [ ] **Step 2: Migration adding `voided_at` / `void_reason` to `electronic_documents`** (fixes the silently dropped void reason)

```php
Schema::table('electronic_documents', function (Blueprint $table) {
    $table->timestamp('voided_at')->nullable()->after('authorization_date');
    $table->string('void_reason', 300)->nullable()->after('voided_at');
});
```
Add both to `ElectronicDocument::$fillable` and `'voided_at' => 'datetime'` to `$casts`.

- [ ] **Step 3: Rewrite `ApiKey` model**

```php
<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use BelongsToTenant, HasFactory;

    /** Alcances disponibles (scope => etiqueta para la UI). */
    public const SCOPES = [
        'documents:read' => 'Consultar documentos (listar, ver, estado, RIDE, XML)',
        'documents:write' => 'Emitir documentos (crear, enviar, anular, reenviar correo)',
        'customers:read' => 'Consultar clientes',
        'customers:write' => 'Crear y editar clientes',
        'products:read' => 'Consultar productos',
        'products:write' => 'Crear y editar productos',
        'catalogs:read' => 'Consultar catálogos del SRI',
    ];

    public const MAX_ACTIVE_PER_TENANT = 10;

    public const PLAN_RATE_LIMITS = ['enterprise' => 300, 'profesional' => 120, 'negocio' => 60];

    public const DEFAULT_RATE_LIMIT = 30;

    protected $fillable = [
        'tenant_id', 'created_by', 'name', 'key_hash', 'key_prefix', 'permissions',
        'rate_limit_per_minute', 'last_used_at', 'last_used_ip', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public static function generatePlainKey(): string
    {
        return 'fec_'.Str::random(40);
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public static function prefixFrom(string $key): string
    {
        return substr($key, 0, 12);
    }

    /** Búsqueda sin el scope de tenant (el middleware corre sin usuario autenticado). */
    public static function findByPlainKey(string $key): ?self
    {
        return static::withoutGlobalScopes()->where('key_hash', static::hashKey($key))->first();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function scopes(): array
    {
        return array_values(array_unique($this->permissions ?: ['*']));
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes();

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }

    public function planRateLimit(): int
    {
        $slug = $this->tenant?->currentPlan?->slug;

        return self::PLAN_RATE_LIMITS[$slug] ?? self::DEFAULT_RATE_LIMIT;
    }

    public function effectiveRateLimit(): int
    {
        $own = (int) ($this->rate_limit_per_minute ?: PHP_INT_MAX);

        return max(1, min($own, $this->planRateLimit()));
    }

    /** Registra el uso como máximo una vez por minuto (evita un UPDATE por petición). */
    public function touchUsage(?string $ip = null): void
    {
        if ($this->last_used_at && $this->last_used_at->gt(now()->subMinute()) && $this->last_used_ip === $ip) {
            return;
        }

        $this->forceFill(['last_used_at' => now(), 'last_used_ip' => $ip])->saveQuietly();
    }

    /** Reemplaza la llave por una nueva y devuelve el valor en claro (se muestra una sola vez). */
    public function rotate(): string
    {
        $plain = static::generatePlainKey();
        $this->forceFill([
            'key_hash' => static::hashKey($plain),
            'key_prefix' => static::prefixFrom($plain),
            'is_active' => true,
        ])->save();

        return $plain;
    }

    public static function availablePermissions(): array
    {
        return self::SCOPES;
    }
}
```

- [ ] **Step 4: Factory**

```php
<?php

namespace Database\Factories;

use App\Models\Tenant\ApiKey;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plain = ApiKey::generatePlainKey();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true),
            'key_hash' => ApiKey::hashKey($plain),
            'key_prefix' => ApiKey::prefixFrom($plain),
            'permissions' => ['*'],
            'rate_limit_per_minute' => 60,
            'is_active' => true,
        ];
    }

    /** Devuelve [modelo, llave en claro] para usarla en pruebas. */
    public function withPlainKey(string $plain): static
    {
        return $this->state(['key_hash' => ApiKey::hashKey($plain), 'key_prefix' => ApiKey::prefixFrom($plain)]);
    }
}
```

- [ ] **Step 5: Unit test** (`tests/Unit/ApiKeyModelTest.php`, uses `RefreshDatabase`): `hasScope` with `['*']`, with a list, `isValid` false when expired or inactive, `effectiveRateLimit` = min(key, plan) using a `Plan::factory()->create(['slug' => 'negocio'])` tenant and `rate_limit_per_minute => 500` → 60, `rotate()` changes hash+prefix and old plain key no longer resolves.

- [ ] **Step 6: Run** `php artisan test --filter=ApiKeyModelTest --compact` → PASS. `vendor/bin/pint` on touched files. Commit `feat(api): harden api_keys schema and model`.

---

### Task 2: Middleware — `VerifyApiKey`, `RequireApiScope`, `ApiRateLimiting`

**Files:**
- Modify: `backend/app/Http/Middleware/VerifyApiKey.php` (rewrite)
- Create: `backend/app/Http/Middleware/RequireApiScope.php`
- Modify: `backend/app/Http/Middleware/ApiRateLimiting.php` (rewrite)
- Modify: `backend/bootstrap/app.php` (aliases: `api.rate` → `ApiRateLimiting`, add `api.scope`)
- Test: `backend/tests/Feature/Api/Ext/ExternalApiAuthTest.php`

**Interfaces:**
- Produces: request attributes `api_key` (`ApiKey`) and `api_tenant` (`Tenant`); `request->user()` = acting user (tenant owner, else first active `tenant_owner`/`admin` user). Helper `App\Support\ApiError::json(string $code, string $message, int $status, array $extra = [])` returning the envelope `{success:false, error, message, ...extra}`.

- [ ] **Step 1: Failing tests** (`ExternalApiAuthTest`, `CreatesTestTenant` + `RefreshDatabase`; in `setUp` set `$this->plan->update(['has_api_access' => true])` and `$this->tenant->syncPlanLimits($this->plan)`; helper `makeKey(array $overrides = []): array{ApiKey,string}` creating a key for `$this->tenant` with a known plain key; helper `ext(string $method, string $uri, array $data = [], ?string $key = null)` sending `Authorization: Bearer $key`):
  - `test_missing_key_returns_401_missing_api_key` — `GET /api/v1/ext/me` → 401 json `error=missing_api_key`.
  - `test_query_string_key_is_ignored` — `GET /api/v1/ext/me?api_key=…` → 401 `missing_api_key`.
  - `test_invalid_key_returns_401` → `invalid_api_key`.
  - `test_inactive_key_returns_401_invalid` (`is_active=false`).
  - `test_expired_key_returns_401_expired_api_key` (`expires_at = now()->subDay()`).
  - `test_x_api_key_header_also_works` → 200.
  - `test_plan_without_api_access_returns_403_with_upgrade_url` (`$this->plan->update(['has_api_access'=>false]); $this->tenant->syncPlanLimits($this->plan)`) → 403 `api_access_not_allowed`, `upgrade_url` ends with `/settings/subscription`.
  - `test_no_active_subscription_returns_403_subscription_required` (`$this->subscription->update(['status'=>SubscriptionStatus::EXPIRED])`).
  - `test_suspended_tenant_returns_403_tenant_inactive` (`$this->tenant->update(['status'=>TenantStatus::SUSPENDED])`).
  - `test_scope_is_enforced` — key with `['customers:read']` → `GET /ext/documents` 403 `insufficient_scope` + `required_scope=documents:read`; `GET /ext/customers` 200.
  - `test_wildcard_scope_allows_everything`.
  - `test_rate_limit_headers_and_429` — key `rate_limit_per_minute=2`: two requests 200 with `X-RateLimit-Limit: 2`, `X-RateLimit-Remaining: 1` then `0`; third → 429 `rate_limit_exceeded` with `Retry-After`.
  - `test_plan_limit_caps_key_limit` — plan slug `negocio`, key limit 500 → header `X-RateLimit-Limit: 60`.
  - `test_usage_is_recorded` — after one call `last_used_at` not null and `last_used_ip` = `127.0.0.1`.
  - `test_acting_user_falls_back_to_admin_when_owner_missing` — `$this->tenant->update(['owner_id'=>null])` → 200 and `GET /ext/me` still works.
  Run: expect failures (route `/ext/me` missing → 404 etc.).

- [ ] **Step 2: `App\Support\ApiError`**

```php
<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiError
{
    public static function json(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'error' => $code,
            'message' => $message,
        ], $extra), $status);
    }
}
```

- [ ] **Step 3: `VerifyApiKey`**

```php
<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Tenant\ApiKey;
use App\Models\User;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica integraciones externas con una API key (tabla api_keys).
 * Orden de comprobaciones: llave → tenant accesible → suscripción vigente →
 * plan con API → usuario que actúa. Deja `api_key` y `api_tenant` en los
 * atributos de la petición (nunca en el input) y fija request->user().
 */
class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $this->extractKey($request);

        if ($plain === null) {
            return ApiError::json('missing_api_key', 'Envía tu API key en la cabecera Authorization: Bearer fec_… (o X-API-Key).', 401);
        }

        $apiKey = ApiKey::findByPlainKey($plain);

        if (! $apiKey || ! $apiKey->is_active) {
            return ApiError::json('invalid_api_key', 'API key inválida o desactivada.', 401);
        }

        if ($apiKey->isExpired()) {
            return ApiError::json('expired_api_key', 'La API key caducó. Genera una nueva desde Configuración → API e integraciones.', 401);
        }

        $tenant = $apiKey->tenant()->withoutGlobalScopes()->with('currentPlan')->first();

        if (! $tenant || ! $tenant->isAccessible()) {
            return ApiError::json('tenant_inactive', 'La cuenta está suspendida o inactiva.', 403);
        }

        if (! $tenant->activeSubscription()->exists()) {
            return ApiError::json('subscription_required', 'La cuenta no tiene una suscripción vigente.', 403, [
                'upgrade_url' => rtrim(config('app.url'), '/').'/settings/subscription',
            ]);
        }

        if (! $tenant->hasFeature('api_access')) {
            return ApiError::json('api_access_not_allowed', 'Tu plan no incluye acceso a la API. Disponible desde el plan Negocio.', 403, [
                'upgrade_url' => rtrim(config('app.url'), '/').'/settings/subscription',
            ]);
        }

        $actor = $this->resolveActor($tenant);

        if (! $actor) {
            return ApiError::json('tenant_inactive', 'La cuenta no tiene un usuario administrador activo.', 403);
        }

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_tenant', $tenant);
        $request->setUserResolver(fn () => $actor);

        $apiKey->touchUsage($request->ip());

        return $next($request);
    }

    private function extractKey(Request $request): ?string
    {
        $bearer = $request->bearerToken();
        if (is_string($bearer) && str_starts_with($bearer, 'fec_')) {
            return $bearer;
        }

        $header = $request->header('X-API-Key');

        return is_string($header) && $header !== '' ? trim($header) : null;
    }

    private function resolveActor(\App\Models\Tenant\Tenant $tenant): ?User
    {
        $owner = $tenant->owner;
        if ($owner && $owner->is_active) {
            return $owner;
        }

        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('role', [UserRole::TENANT_OWNER->value, UserRole::ADMIN->value])
            ->orderBy('id')
            ->first();
    }
}
```
(Check `User` has `is_active`; the `User` type in the frontend exposes it, so the column exists.)

- [ ] **Step 4: `RequireApiScope`**

```php
<?php

namespace App\Http\Middleware;

use App\Models\Tenant\ApiKey;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: ->middleware('api.scope:documents:write'). */
class RequireApiScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey instanceof ApiKey) {
            return ApiError::json('missing_api_key', 'Petición sin API key.', 401);
        }

        if (! $apiKey->hasScope($scope)) {
            return ApiError::json('insufficient_scope', "Esta API key no tiene el alcance {$scope}.", 403, [
                'required_scope' => $scope,
                'key_scopes' => $apiKey->scopes(),
            ]);
        }

        return $next($request);
    }
}
```

- [ ] **Step 5: `ApiRateLimiting` rewrite** — key `api_rate:{apiKey->id}`, limit `effectiveRateLimit()`, decay 60 s, headers on every response, 429 via `ApiError::json('rate_limit_exceeded', "Demasiadas solicitudes. Intenta de nuevo en {$retryAfter} segundos.", 429, ['retry_after' => $retryAfter])` + headers `X-RateLimit-Limit`, `X-RateLimit-Remaining: 0`, `Retry-After`. If no `api_key` attribute (misuse), return 401 `missing_api_key` (fail closed).

- [ ] **Step 6: Aliases** in `bootstrap/app.php`: `'api.rate' => \App\Http\Middleware\ApiRateLimiting::class`, `'api.scope' => \App\Http\Middleware\RequireApiScope::class`. Add a temporary `GET v1/ext/me` route returning `['ok' => true]` so auth tests run (replaced in Task 4).

- [ ] **Step 7: Run** `php artisan test --filter=ExternalApiAuthTest --compact` → PASS. Pint. Commit `feat(api): API-key auth, scopes and per-key rate limiting for /ext`.

---

### Task 3: API key management (Sanctum) — `/api/v1/api-keys`

**Files:**
- Create: `backend/app/Http/Controllers/Api/V1/ApiKeyController.php`
- Create: `backend/app/Http/Requests/Api/StoreApiKeyRequest.php`, `UpdateApiKeyRequest.php`
- Create: `backend/app/Http/Resources/ApiKeyResource.php`
- Modify: `backend/routes/api.php` (inside the `auth:sanctum` group, behind `plan.feature:api_access`)
- Test: `backend/tests/Feature/Api/ApiKeyManagementTest.php`

**Interfaces:**
- Produces routes: `GET api-keys` → `{data: {api_keys: [...], scopes: {scope: label}, limits: {max_keys, plan_rate_limit}}}`; `POST api-keys` (201) → `{data: {api_key, plain_key}}`; `PATCH api-keys/{apiKey}`; `POST api-keys/{apiKey}/rotate` → `{data: {api_key, plain_key}}`; `DELETE api-keys/{apiKey}`.
- `ApiKeyResource` fields: `id, name, key_prefix, scopes, rate_limit_per_minute, effective_rate_limit, last_used_at, last_used_ip, expires_at, is_active, is_expired, created_by, created_at`.

- [ ] **Step 1: Failing tests**: list empty + scopes map; create returns `plain_key` starting `fec_` (44 chars) and never again in list; create validates `name` required, `scopes` subset of `ApiKey::SCOPES` keys, `expires_in_days` in `[30,90,365]` or null, `rate_limit_per_minute` 1..plan limit; 11th active key → 422 `max_keys_reached`; viewer role → 403; plan without api access → 403 `feature_not_available`; rotate invalidates old key (`GET /ext/me` with old → 401, with new → 200); PATCH `is_active=false` → old key 401; delete removes; another tenant's key → 404.

- [ ] **Step 2: Requests**

`StoreApiKeyRequest::rules()`:
```php
return [
    'name' => ['required', 'string', 'max:100'],
    'scopes' => ['required', 'array', 'min:1'],
    'scopes.*' => ['string', Rule::in(array_merge(['*'], array_keys(ApiKey::SCOPES)))],
    'expires_in_days' => ['nullable', 'integer', Rule::in([30, 90, 365])],
    'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
];
```
`UpdateApiKeyRequest`: same for `name`/`scopes` as `sometimes`, plus `'is_active' => ['sometimes', 'boolean']`.
Both `authorize()`: `in_array($this->user()?->role?->value ?? $this->user()?->role, ['tenant_owner', 'admin'], true)` (role may be an enum or string).

- [ ] **Step 3: Controller** (extends `ApiController`; every query `ApiKey::where('tenant_id', $tenantId)`; `show`-less):
  - `index`: keys ordered by `created_at desc`; `limits.plan_rate_limit` = `ApiKey::PLAN_RATE_LIMITS[$tenant->currentPlan?->slug] ?? ApiKey::DEFAULT_RATE_LIMIT`.
  - `store`: role check (403 `forbidden` "Solo el propietario o un administrador puede gestionar llaves"), count active < 10 else `error('Alcanzaste el máximo de 10 llaves activas. Desactiva o elimina una.', 422, ['limit' => 10])` with `'error' => 'max_keys_reached'` (use `response()->json` to include the code), create with `created_by`, `expires_at = now()->addDays(n)`, `rate_limit_per_minute = min(requested ?? planLimit, planLimit)`; return `created(['api_key' => new ApiKeyResource($key), 'plain_key' => $plain], 'Guarda esta llave ahora: no volverá a mostrarse.')`.
  - `update`, `rotate` (`$plain = $key->rotate()`), `destroy` (204 → use `success(null, 'Llave eliminada.')`).
  - Route binding: `Route::apiResource('api-keys', ApiKeyController::class)->only(['index','store','update','destroy'])` + `Route::post('api-keys/{api_key}/rotate', ...)` inside `Route::middleware('plan.feature:api_access')->group(...)`. Use explicit lookup `ApiKey::where('tenant_id', ...)->findOrFail($id)` instead of implicit binding so cross-tenant ids → 404.

- [ ] **Step 4: Run** `--filter=ApiKeyManagementTest` → PASS. Pint. Commit `feat(api): API key management endpoints`.

---

### Task 4: External API controllers + routes + idempotency

**Files:**
- Create: `backend/app/Services/Api/IdempotencyStore.php`
- Create: `backend/app/Http/Controllers/Api/V1/Ext/MeController.php`, `CompanyController.php`, `DocumentController.php`, `CustomerController.php`, `ProductController.php`
- Modify: `backend/routes/api.php` (replace the `v1/ext` group)
- Modify: `backend/app/Http/Controllers/Api/V1/DocumentController.php` (add `access_key` filter in `index`; make `resendEmail` null-safe: `$document->customer?->email`)
- Modify: `backend/app/Http/Controllers/Api/V1/CatalogController.php` (`documentTypes()` uses `$type->value` instead of undefined `sriCode()`)
- Modify: `backend/app/Http/Controllers/Api/V1/ProductController.php` (map `sku` → `aux_code` in store/update)
- Modify: `backend/app/Http/Requests/Api/DocumentRequest.php` (validate `subtotal_8`, `subtotal_13`, `send`)
- Test: `backend/tests/Feature/Api/Ext/ExternalDocumentsApiTest.php`, `ExternalCatalogApiTest.php`

**Interfaces:**
- `IdempotencyStore::run(Tenant $tenant, ?string $key, array $payload, Closure $handler): Response` — no key → run handler; else lock `idem-lock:{tenant}:{sha1(key)}` 10 s (busy → 409 `idempotency_in_progress`), cached `{hash, status, body}` → same hash ⇒ replay with header `Idempotent-Replayed: true`, other hash ⇒ 409 `idempotency_key_reused`; stores responses with status < 500 && != 429 for 24 h. Key ≤ 128 chars else 422 `validation_error`.

- [ ] **Step 1: Failing tests** (`Queue::fake()`, `Storage::fake`, key with `['*']`):
  - `test_me_returns_tenant_plan_limits_and_key` — `GET /ext/me` → `data.tenant.name`, `data.plan.slug`, `data.limits.documents_per_month`, `data.usage.documents_this_period`, `data.api_key.scopes`.
  - `test_companies_lists_establishments_and_emission_points` — ids match `$this->company/$this->branch/$this->emissionPoint`.
  - `test_create_document_with_send_dispatches_processing` — payload from `CreatesTestTenant` customer + one item, `send` default → 201, `data.document.status === 'processing'`, `Queue::assertPushed(ProcessDocumentJob::class)`.
  - `test_create_document_without_send_leaves_draft`.
  - `test_idempotency_replays_same_response` — two POSTs same `Idempotency-Key` → same `data.document.id`, second has header `Idempotent-Replayed: true`, only one document row.
  - `test_idempotency_key_with_different_body_conflicts` → 409 `idempotency_key_reused`.
  - `test_validation_error_shape` — missing `items` → 422 `error=validation_error`, `errors.items`.
  - `test_sri_prevalidation_failure_returns_422_with_document` — customer "Consumidor Final" (`identification 9999999999999`) with total 100 → 422 `errors.sri` non-empty, `data.document.status === 'rejected'`.
  - `test_plan_limit_reached_returns_403` — `$this->plan->update(['max_documents_per_month'=>1]); syncPlanLimits`; create one doc via factory this month → 403 `plan_limit_reached`.
  - `test_list_filters_and_caps_per_page` — `per_page=500` → `meta.per_page == 100`; `access_key` filter returns 1.
  - `test_show_other_tenant_document_is_404`.
  - `test_status_endpoint`, `test_send_draft`, `test_void_authorized_persists_reason` (`void_reason` saved), `test_email_requires_authorized`.
  - `test_ride_streams_pdf` — `Content-Type: application/pdf`, body starts with `%PDF`; `?url=1` → JSON `data.url` contains `/api/v1/public/documents/`.
  - `test_xml_not_available_is_404` → `error=xml_not_available`.
  - Catalog test: customers list/lookup(200/404)/create/patch, products list/create (with `sku` persisted as `aux_code`)/patch, 5 catalogs incl. `document-types` 200.

- [ ] **Step 2: `IdempotencyStore`** (`Cache` facade; `payload` hash = `hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))`; response serialized as `['status' => $r->getStatusCode(), 'body' => json_decode($r->getContent(), true), 'hash' => $hash]`; replay via `response()->json($body, $status)->header('Idempotent-Replayed', 'true')`).

- [ ] **Step 3: `Ext\DocumentController extends \App\Http\Controllers\Api\V1\DocumentController`**

```php
protected function authorizeDocument(Request $request, ElectronicDocument $document): void
{
    if ($document->tenant_id !== $request->user()->tenant_id) {
        throw new NotFoundHttpException('Documento no encontrado.');
    }
}

public function index(Request $request): JsonResponse
{
    $request->merge(['per_page' => min(max((int) $request->input('per_page', 15), 1), 100)]);
    return parent::index($request);
}

public function store(DocumentRequest $request): JsonResponse
{
    $tenant = $request->attributes->get('api_tenant');
    return app(IdempotencyStore::class)->run(
        $tenant,
        $request->header('Idempotency-Key'),
        $request->all(),
        function () use ($request) {
            $created = parent::store($request);
            if ($created->getStatusCode() !== 201) {
                return $this->translateError($created);   // 403 sin suscripción → subscription_required / plan_limit_reached; 422 → validation_error
            }
            if (! $request->boolean('send', true)) {
                return $created;
            }
            $id = data_get($created->getData(true), 'data.document.id');
            $document = ElectronicDocument::withoutGlobalScopes()->findOrFail($id);
            $sent = parent::send($request, $document);
            if ($sent->getStatusCode() >= 400) {
                $body = $sent->getData(true);
                return response()->json([
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => $body['message'] ?? 'El documento no pasó las validaciones del SRI.',
                    'errors' => ['sri' => array_values((array) ($body['errors'] ?? []))],
                    'data' => ['document' => new DocumentResource($document->fresh()->load(['customer', 'company', 'items']))],
                ], $sent->getStatusCode() === 422 ? 422 : 409);
            }
            return response()->json($sent->getData(true), 201);
        }
    );
}
```
`translateError(JsonResponse $r)`: maps message containing "suscripción" → `subscription_required` (403), "límite de documentos" → `plan_limit_reached` (403), else keeps status and adds `'error' => $status === 422 ? 'validation_error' : 'request_failed'`.
`status()` → `parent::checkStatus`; `send()`/`void()`/`email()` (→ `parent::resendEmail`) delegate; `ride(Request, ElectronicDocument)` → `$request->boolean('url') ? parent::downloadRide(...) : parent::streamRide(...)`; `xml()` → 404 `xml_not_available` when `xml_signed_path` empty, else `parent::streamXml` / `downloadXml`.
Also override the 422 from FormRequest: Laravel's default validation renderer already returns `{message, errors}`; add `'error' => 'validation_error'` by handling `ValidationException` in `bootstrap/app.php` **only for `ext/*` paths** (`$request->is('api/v1/ext/*')`).

- [ ] **Step 4: `Ext\CustomerController extends CustomerController`** — override `authorizeCustomer` → 404; `index` caps `per_page`; `lookup(Request)` validates `identification` required string max 20 → `Customer::where('tenant_id', …)->where('identification', $id)->first()` → 200 `{customer}` or 404 `error=not_found`. `Ext\ProductController` likewise (`authorizeProduct` → 404, caps). `Ext\CompanyController@index` → companies of tenant with `branches.emissionPoints` (only `is_active`), fields `id, ruc, business_name, trade_name, sri_environment, branches[{id, code, name, address, emission_points[{id, code, name}]}]`. `Ext\MeController@show` → tenant (`id, name, email`), plan (`slug, name`), limits (`documents_per_month` (-1 unlimited), `effective_document_limit`), usage (`documents_this_period`, `period_start`), subscription (`status, ends_at`), api_key (`name, key_prefix, scopes, rate_limit_per_minute, effective_rate_limit, expires_at`).

- [ ] **Step 5: Routes** (replace lines 360-379 of `routes/api.php`):

```php
Route::prefix('v1/ext')->name('ext.')->middleware(['api.key', 'api.rate'])->group(function () {
    Route::get('me', [Ext\MeController::class, 'show'])->name('me');
    Route::get('companies', [Ext\CompanyController::class, 'index'])->middleware('api.scope:documents:read')->name('companies');

    Route::middleware('api.scope:documents:read')->group(function () {
        Route::get('documents', [Ext\DocumentController::class, 'index']);
        Route::get('documents/{document}', [Ext\DocumentController::class, 'show']);
        Route::get('documents/{document}/status', [Ext\DocumentController::class, 'status']);
        Route::get('documents/{document}/ride', [Ext\DocumentController::class, 'ride']);
        Route::get('documents/{document}/xml', [Ext\DocumentController::class, 'xml']);
    });
    Route::middleware('api.scope:documents:write')->group(function () {
        Route::post('documents', [Ext\DocumentController::class, 'store']);
        Route::post('documents/{document}/send', [Ext\DocumentController::class, 'send']);
        Route::post('documents/{document}/void', [Ext\DocumentController::class, 'void']);
        Route::post('documents/{document}/email', [Ext\DocumentController::class, 'email']);
    });

    Route::middleware('api.scope:customers:read')->group(function () {
        Route::get('customers', [Ext\CustomerController::class, 'index']);
        Route::get('customers/lookup', [Ext\CustomerController::class, 'lookup']);
        Route::get('customers/{customer}', [Ext\CustomerController::class, 'show']);
    });
    Route::middleware('api.scope:customers:write')->group(function () {
        Route::post('customers', [Ext\CustomerController::class, 'store']);
        Route::match(['put', 'patch'], 'customers/{customer}', [Ext\CustomerController::class, 'update']);
    });

    Route::middleware('api.scope:products:read')->group(function () {
        Route::get('products', [Ext\ProductController::class, 'index']);
        Route::get('products/{product}', [Ext\ProductController::class, 'show']);
    });
    Route::middleware('api.scope:products:write')->group(function () {
        Route::post('products', [Ext\ProductController::class, 'store']);
        Route::match(['put', 'patch'], 'products/{product}', [Ext\ProductController::class, 'update']);
    });

    Route::prefix('catalogs')->middleware('api.scope:catalogs:read')->group(function () {
        Route::get('identification-types', [CatalogController::class, 'identificationTypes']);
        Route::get('document-types', [CatalogController::class, 'documentTypes']);
        Route::get('payment-methods', [CatalogController::class, 'paymentMethods']);
        Route::get('tax-rates', [CatalogController::class, 'taxRates']);
        Route::get('retention-codes', [CatalogController::class, 'retentionCodes']);
    });
});
```
Note: route-model binding for `{document}` must bypass the tenant global scope explicitly (`ElectronicDocument::withoutGlobalScopes()`) — with no authenticated guard user the scope is already a no-op, but bind explicitly in `RouteServiceProvider`/`AppServiceProvider` is NOT needed; keep implicit binding and rely on the 404 override. Also `catalogs:read` is implicit: `RequireApiScope` treats `catalogs:read` as always granted (add `if ($scope === 'catalogs:read') return $next($request);`).

- [ ] **Step 6: Run** `--filter="Ext|ApiKey"` and then the full suite → PASS. Pint. Commit `feat(api): external integration API (/api/v1/ext) with idempotent emission`.

---

### Task 5: OpenAPI 3.1 file + public docs page + links

**Files:**
- Create: `frontend/public/docs/openapi.yaml`
- Create: `frontend/src/content/api-docs.ts` (sections, endpoints, examples), `frontend/src/app/(marketing)/docs/api/page.tsx`, `frontend/src/components/marketing/docs/{docs-shell,code-block,endpoint-card}.tsx`
- Modify: `frontend/src/lib/landing/seo.ts` (`buildSitemap` adds `/docs/api`), `frontend/src/lib/landing/llms.ts` (link "API para desarrolladores"), `frontend/src/components/marketing/footer.tsx` (link in "Producto" column + `NEXT_ROUTES`), `frontend/src/content/landing.ts` (FAQ "¿Tienen API para integrar mi sistema?" → sí, planes Negocio+; link `/docs/api`), `frontend/src/app/robots.ts` (nothing to change; verify `/docs` allowed)
- Test: `frontend/tests/unit/api-docs.test.tsx` (renders all sections/anchors; every endpoint in `api-docs.ts` exists in `openapi.yaml` paths), update `frontend/tests/unit/seo.test.ts` if it asserts exact sitemap entries, `frontend/tests/e2e/landing.spec.ts` (+ `/docs/api` 200, h1, sidebar links, `openapi.yaml` 200 `text/yaml`)

- [ ] **Step 1:** Write `openapi.yaml` (info, servers `https://facturon.ec/api/v1/ext`, `securitySchemes: bearerAuth (http bearer), apiKeyHeader (X-API-Key)`, all paths from spec §5 with request/response schemas `Document`, `DocumentInput`, `DocumentItemInput`, `Customer`, `CustomerInput`, `Product`, `ProductInput`, `Company`, `Me`, `Error`, `Paginated*`), including `Idempotency-Key` header param and `x-rate-limit` headers.
- [ ] **Step 2:** Docs page: marketing nav + two-column layout (sticky sidebar with anchors; content with sections: Introducción, Autenticación, Alcances y límites, Idempotencia, Flujo para emitir (5 pasos: `GET /companies` → `POST /customers` o `lookup` → `POST /documents` → `GET /status` → `GET /ride|xml`), reference cards per endpoint (method badge, path, scope pill, params table, example request/response), Errores (table from spec §4), Ejemplos (curl, Node fetch, PHP Guzzle), Preguntas frecuentes (ambiente de pruebas, tiempos del SRI, contingencia), footer). Explicit colors like the rest of marketing (no `dark:`), Bricolage display font, `metadata` title "Documentación de la API — Facturón", canonical `/docs/api`, JSON-LD `TechArticle`.
- [ ] **Step 3:** Links: footer, FAQ, llms.txt, sitemap. `MARKETING_PATHS` in `src/proxy.ts` must let `/docs` through (check `proxy.test.ts`).
- [ ] **Step 4:** `pnpm test`, `pnpm typecheck`, `pnpm lint`, `pnpm build && pnpm e2e` (fixture mode). Commit `feat(docs): public API documentation at /docs/api + OpenAPI 3.1`.

---

### Task 6: Panel page "API e integraciones"

**Files:**
- Create: `frontend/src/lib/api/queries/api-keys.ts` (`useApiKeys`, `useCreateApiKey`, `useUpdateApiKey`, `useRotateApiKey`, `useDeleteApiKey`; types `ApiKey`, `ApiKeysResponse`)
- Create: `frontend/src/app/(panel)/settings/api/page.tsx` (server: metadata + PageHeader + `<ApiKeysManager />`), `api-keys-manager.tsx` (client), `create-key-dialog.tsx`, `reveal-key-dialog.tsx`
- Modify: `frontend/src/app/(panel)/settings/page.tsx` (add section `{ title: "API e integraciones", description: "Llaves de API, límites y documentación", href: "/settings/api", icon: Plug }`; Seguridad description → "Contraseña, 2FA y sesiones")
- Test: `frontend/tests/unit/api-keys-manager.test.tsx` (mock `@/lib/api/client`: 403 `feature_not_available` → upsell with link `/settings/subscription`; 200 list → table rows; create → reveal dialog shows `plain_key` and copy button)

- [ ] **Step 1:** Query module using `api.get("api-keys")` etc.; `ClientApiError` with `payload.error === 'feature_not_available'` detected by helper `isFeatureLocked(err)`.
- [ ] **Step 2:** Manager states: loading spinner; locked → `Card` "La API está disponible desde el plan Negocio" with bullets (emitir desde tu ERP/tienda, consultar autorizaciones, RIDE/XML) + buttons "Ver planes" (`/settings/subscription`) and "Ver documentación" (`/docs/api`, target blank); ready → header actions "Nueva llave" + "Documentación"; table columns Nombre, Prefijo (`code`), Alcances (badges), Límite/min, Último uso (`formatDate` + IP), Caduca, Estado (badge activa/desactivada/caducada), acciones (Rotar, Activar/Desactivar, Eliminar with `confirm()`); quick-start card with `curl -H "Authorization: Bearer <tu-llave>" https://facturon.ec/api/v1/ext/me`.
- [ ] **Step 3:** Create dialog: name input, scope checkboxes grouped (Documentos, Clientes, Productos) with "Acceso total" toggle, expiry select (Nunca/30/90/365 días), rate limit number (hint: máximo del plan `limits.plan_rate_limit`); submit → reveal dialog (`plain_key` in a `code` block, Copy via `navigator.clipboard.writeText` + toast, warning "no volverá a mostrarse"). Rotate reuses reveal dialog.
- [ ] **Step 4:** `pnpm test`, `pnpm typecheck`, `pnpm lint`. Commit `feat(panel): API keys management page (Configuración → API e integraciones)`.

---

### Task 7: Deploy + verify + memory

- [ ] Push `main`; on `159.89.40.217` run `/root/deploy-new.sh` detached (`setsid nohup … &`), wait for `DEPLOY_NEW_DONE`; confirm `Migrating: 2026_09_13_…` in the log.
- [ ] Verify: `curl -s https://facturon.ec/api/v1/ext/me` → 401 `missing_api_key`; `curl -s -H "Authorization: Bearer fec_x" …` → 401 `invalid_api_key`; `curl -sI https://facturon.ec/docs/api` → 200; `https://facturon.ec/docs/openapi.yaml` → 200; `/settings/api` → 307 to login (unauthenticated).
- [ ] Update memory: `integration-api.md` (design decisions, gotchas: BelongsToTenant scope, owner fallback, idempotency store, where docs live) + `MEMORY.md` index; note in `production-deployment.md` that `/ext` is live.
