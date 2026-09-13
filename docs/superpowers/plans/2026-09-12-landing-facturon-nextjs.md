# Landing Facturón en Next.js — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar la landing Blade por una landing de alto impacto en Next.js (`/`), con demo animada, capturas reales, precios desde el CMS, SEO/GEO completo, y dejar el dashboard en `/dashboard`; de paso, corregir que los formularios de login/registro se vacíen al fallar.

**Architecture:** Grupo de rutas `src/app/(marketing)/` en el frontend Next.js 16 existente; componentes en `src/components/marketing/`; lógica pura y testeable en `src/lib/landing/`; contenido en `src/content/landing.ts`. Laravel expone `GET /api/v1/public/landing` (planes + textos del CMS) cacheado 300 s e invalidado desde Filament. nginx deja de enrutar `/` a Laravel.

**Tech Stack:** Next.js 16.2 (App Router, React 19, `next/font`, `next/image`, Metadata API), Tailwind v4, `motion` (animaciones), Vitest + Testing Library (unit), Playwright (e2e), Laravel 12 + PHPUnit (backend), pnpm 10.15, Node 22.

**Spec:** `docs/superpowers/specs/2026-09-12-landing-facturon-nextjs-design.md`

## Global Constraints

- Paleta: navy `#0B1220`, superficies `#101B31` / `#162037`, azul `#2B54E4` (hover `#2446C4`), cuerpo blanco + `slate-50`, texto `slate-900`/`slate-600`; degradado bandera `#FFCE00 → #0653C6 → #EF3340` solo como acento fino; `emerald` solo para "AUTORIZADO"/éxito.
- Tipografía: Bricolage Grotesque 700/800 (titulares, `font-display`), Geist (texto), Geist Mono (clave de acceso, precios). Todo self-hosted con `next/font`.
- Componentes de la landing con colores explícitos: **prohibido** `dark:` y tokens semánticos (`bg-background`, `text-foreground`, `text-muted-foreground`…) dentro de `src/components/marketing/` y `src/app/(marketing)/`.
- Sin dark-mode toggle en la landing. Sin analítica de terceros. Sin peticiones a dominios externos en runtime.
- Copy en español con tildes. Claims SRI: "comprobantes autorizados por el SRI"; nunca "software/sistema autorizado por el SRI". Nunca "gratis"/"prueba gratis" (todos los planes tienen `trial_days = 0`). Nada de testimonios ni contadores de uso.
- Contacto: WhatsApp `13347324056` (se muestra "+1 334 732 4056"), correo `info@amephia.com`. Tiendas: URLs vacías → "Próximamente".
- Única dependencia nueva de runtime: `motion`. Presupuesto: JS propio de la landing < 120 KB gz; imágenes ≤ 200 KB servidas; LCP < 2,5 s; CLS 0; Lighthouse ≥ 90 ×4 (móvil).
- Commits pequeños por tarea, mensajes en español con prefijo convencional (`feat(landing): …`), terminados con `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.
- Todo comando del frontend se ejecuta desde `frontend/` con `pnpm`; los del backend desde `backend/`.

---

## Estructura de archivos

**Frontend (`frontend/`)**
- `package.json` — scripts `test`, `test:watch`, `test:e2e`, `typecheck`; deps nuevas.
- `vitest.config.ts`, `tests/setup.ts`, `playwright.config.ts` — tooling de pruebas.
- `src/app/(marketing)/layout.tsx` — fuente display, metadata, skip link, botón WhatsApp.
- `src/app/(marketing)/page.tsx` — landing (`/`): carga datos, JSON-LD, compone secciones.
- `src/app/(panel)/dashboard/page.tsx` — dashboard movido desde `(panel)/page.tsx`.
- `src/app/robots.ts`, `src/app/sitemap.ts`, `src/app/llms.txt/route.ts` — SEO/GEO.
- `src/app/icon.png`, `src/app/apple-icon.png`, `src/app/favicon.ico` — iconos Facturón.
- `src/content/landing.ts` — todo el copy y los datos estáticos (FAQ, comprobantes, features…).
- `src/lib/landing/config.ts` — contacto, tiendas, `APP_URL`, helpers de WhatsApp.
- `src/lib/landing/pricing.ts` — tipos `LandingPlan`/`LandingData`, formateo y utilidades de precios.
- `src/lib/landing/clave-acceso.ts` — módulo 11 y clave de acceso de 49 dígitos.
- `src/lib/landing/demo-machine.ts` — pasos, duraciones y datos de la demo.
- `src/lib/landing/use-demo-sequence.ts` — hook que avanza la demo.
- `src/lib/landing/fixture.ts`, `src/lib/landing/data.ts` — datos de ejemplo y carga desde la API.
- `src/lib/landing/jsonld.ts`, `src/lib/landing/seo.ts`, `src/lib/landing/llms.ts` — constructores de JSON-LD, robots/sitemap y llms.txt.
- `src/components/marketing/*.tsx` — `logo`, `nav`, `nav-client`, `hero`, `live-demo`, `trust-strip`, `store-badges`, `documents`, `showcase`, `features`, `how-it-works`, `pricing`, `arbitros`, `why`, `faq`, `cta`, `footer`, `whatsapp-button`, `reveal`, `browser-frame`, `phone-frame`.
- `public/marketing/` — capturas, `og.png`, `icon-facturon.svg`, `icon-facturon-512.png`.
- `scripts/capture-screenshots.ts`, `scripts/og/og.html`, `scripts/og/build-og.ts` — generación de assets.
- `tests/unit/*.test.ts(x)`, `tests/e2e/landing.spec.ts` — pruebas.

**Backend (`backend/`)**
- `app/Services/Landing/PublicLandingCache.php` — clave/TTL de caché y `forget()`.
- `app/Http/Controllers/Api/V1/PublicLandingController.php` — endpoint público.
- `routes/api.php` — ruta `public/landing`.
- `app/Models/Billing/Plan.php` — invalidación de caché en `saved`/`deleted`.
- `app/Services/Settings/PricingContentSettings.php` — invalidación al guardar.
- `tests/Feature/PublicLandingTest.php` — reemplaza `tests/Feature/ExampleTest.php`.
- `routes/web.php` — se elimina la ruta `/`; se elimina `resources/views/welcome.blade.php` y `public/robots.txt`.

**Infra / CI / docs**
- `docker/nginx/conf.d/production.conf` — sin `location = /`.
- `frontend/Dockerfile`, `docker/docker-compose.production.yml`, `backend/.env.production` — build args de contacto/tiendas.
- `.github/workflows/ci.yml` — job `frontend-next`.
- `docs/DEPLOYMENT.md` — despliegue del frontend y la landing.

---

### Task 0: Tooling de pruebas en el frontend (Vitest + Playwright)

**Files:**
- Modify: `frontend/package.json`
- Create: `frontend/vitest.config.ts`, `frontend/tests/setup.ts`, `frontend/playwright.config.ts`, `frontend/tests/unit/smoke.test.ts`

**Interfaces:**
- Produces: scripts `pnpm test` (Vitest, `tests/unit/**/*.test.{ts,tsx}`), `pnpm test:e2e` (Playwright, `tests/e2e`, servidor en `:3100` con `LANDING_DATA_SOURCE=fixture`), `pnpm typecheck`.

- [ ] **Step 1: Instalar dependencias**

```bash
cd frontend
pnpm install --frozen-lockfile
pnpm add motion
pnpm add -D vitest @vitejs/plugin-react jsdom @testing-library/react @testing-library/dom @testing-library/jest-dom @playwright/test
pnpm exec playwright install chromium
```

Expected: `node_modules/motion`, `node_modules/vitest` y `node_modules/@playwright/test` existen; `pnpm-lock.yaml` actualizado.

- [ ] **Step 2: Scripts en package.json**

Añadir dentro de `"scripts"`:

```json
"test": "vitest run",
"test:watch": "vitest",
"test:e2e": "playwright test",
"typecheck": "tsc --noEmit"
```

- [ ] **Step 3: Configuración de Vitest y setup**

`frontend/vitest.config.ts`:

```ts
import { fileURLToPath } from "node:url";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vitest/config";

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: { "@": fileURLToPath(new URL("./src", import.meta.url)) },
  },
  test: {
    environment: "jsdom",
    setupFiles: ["./tests/setup.ts"],
    include: ["tests/unit/**/*.test.{ts,tsx}"],
    css: false,
  },
});
```

`frontend/tests/setup.ts`:

```ts
import "@testing-library/jest-dom/vitest";
import { cleanup } from "@testing-library/react";
import { afterEach } from "vitest";

afterEach(() => cleanup());

// jsdom no implementa IntersectionObserver ni matchMedia (los usa `motion`).
class IntersectionObserverStub {
  readonly root = null;
  readonly rootMargin = "";
  readonly thresholds: ReadonlyArray<number> = [];
  observe() {}
  unobserve() {}
  disconnect() {}
  takeRecords(): IntersectionObserverEntry[] {
    return [];
  }
}
Object.defineProperty(globalThis, "IntersectionObserver", {
  writable: true,
  value: IntersectionObserverStub,
});
Object.defineProperty(window, "matchMedia", {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener() {},
    removeListener() {},
    addEventListener() {},
    removeEventListener() {},
    dispatchEvent() {
      return false;
    },
  }),
});
```

- [ ] **Step 4: Prueba de humo**

`frontend/tests/unit/smoke.test.ts`:

```ts
import { describe, expect, it } from "vitest";

describe("tooling", () => {
  it("ejecuta pruebas con jsdom", () => {
    document.body.innerHTML = "<p>hola</p>";
    expect(document.querySelector("p")?.textContent).toBe("hola");
  });
});
```

Run: `pnpm test`
Expected: `1 passed`.

- [ ] **Step 5: Configuración de Playwright**

`frontend/playwright.config.ts`:

```ts
import { defineConfig, devices } from "@playwright/test";

const PORT = 3100;
const BASE_URL = `http://localhost:${PORT}`;

export default defineConfig({
  testDir: "tests/e2e",
  timeout: 30_000,
  fullyParallel: true,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? "github" : "list",
  use: { baseURL: BASE_URL, trace: "retain-on-failure" },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"] } },
    { name: "mobile", use: { ...devices["Pixel 7"] } },
  ],
  webServer: {
    command: `pnpm build && pnpm start -p ${PORT}`,
    url: `${BASE_URL}/robots.txt`,
    reuseExistingServer: !process.env.CI,
    timeout: 300_000,
    env: {
      LANDING_DATA_SOURCE: "fixture",
      NEXT_PUBLIC_APP_URL: BASE_URL,
    },
  },
});
```

- [ ] **Step 6: Commit**

```bash
git add frontend/package.json frontend/pnpm-lock.yaml frontend/vitest.config.ts frontend/tests/setup.ts frontend/tests/unit/smoke.test.ts frontend/playwright.config.ts
git commit -m "chore(frontend): tooling de pruebas (Vitest, Testing Library, Playwright) y dependencia motion"
```

---

### Task 1: Endpoint público `GET /api/v1/public/landing` (Laravel)

**Files:**
- Create: `backend/app/Services/Landing/PublicLandingCache.php`, `backend/app/Http/Controllers/Api/V1/PublicLandingController.php`, `backend/tests/Feature/PublicLandingTest.php`
- Modify: `backend/routes/api.php`, `backend/app/Models/Billing/Plan.php`, `backend/app/Services/Settings/PricingContentSettings.php`
- Delete: `backend/tests/Feature/ExampleTest.php`

**Interfaces:**
- Produces: `GET /api/v1/public/landing` → `{ success: true, message: "Success", data: { plans: LandingPlan[], pricing_content: PricingContent } }` donde `LandingPlan = { id, name, slug, description, price_monthly: float, price_yearly: float, currency, is_featured: bool, yearly_savings_percent: int, features_list: string[] }` y `PricingContent = { eyebrow, title, subtitle, badge_enabled: bool, badge_text, footer_note }`.
- `PublicLandingCache::KEY = 'landing:public'`, `PublicLandingCache::forget()`.

- [ ] **Step 1: Escribir el feature test (falla)**

`backend/tests/Feature/PublicLandingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Services\Settings\PricingContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/public/landing';

    private function makePlan(array $attrs = []): Plan
    {
        return Plan::factory()->create(array_merge([
            'is_active' => true,
            'price_monthly' => 7.99,
            'price_yearly' => 79.90,
            'sort_order' => 1,
        ], $attrs));
    }

    public function test_returns_active_paid_plans_in_order_without_auth(): void
    {
        $this->makePlan(['name' => 'Negocio', 'slug' => 'negocio', 'sort_order' => 2, 'is_featured' => true]);
        $this->makePlan(['name' => 'Emprendedor', 'slug' => 'emprendedor', 'price_monthly' => 2.99, 'price_yearly' => 29.90, 'sort_order' => 1, 'is_featured' => false]);
        $this->makePlan(['name' => 'Inactivo', 'slug' => 'inactivo', 'is_active' => false]);
        $this->makePlan(['name' => 'Gratis', 'slug' => 'gratis', 'price_monthly' => 0, 'price_yearly' => 0]);

        $res = $this->getJson(self::URL);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.plans')
            ->assertJsonPath('data.plans.0.slug', 'emprendedor')
            ->assertJsonPath('data.plans.1.slug', 'negocio')
            ->assertJsonPath('data.plans.1.is_featured', true)
            ->assertJsonPath('data.plans.0.price_monthly', 2.99)
            ->assertJsonPath('data.plans.0.currency', 'USD')
            ->assertJsonPath('data.plans.0.yearly_savings_percent', 17);

        $this->assertNotEmpty($res->json('data.plans.0.features_list'));
    }

    public function test_pricing_content_falls_back_to_defaults_and_reflects_saved_values(): void
    {
        $this->makePlan();

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.pricing_content.eyebrow', 'Planes')
            ->assertJsonPath('data.pricing_content.badge_enabled', true);

        app(PricingContentSettings::class)->save(['title' => 'Título editado', 'badge_enabled' => false]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.pricing_content.title', 'Título editado')
            ->assertJsonPath('data.pricing_content.badge_enabled', false);
    }

    public function test_response_is_cached_and_invalidated_when_a_plan_is_saved(): void
    {
        $this->makePlan(['name' => 'Negocio', 'slug' => 'negocio']);
        $this->getJson(self::URL)->assertJsonPath('data.plans.0.name', 'Negocio');

        // Update por query builder: no dispara eventos, así que la caché sigue viva.
        Plan::query()->where('slug', 'negocio')->update(['name' => 'Renombrado']);
        $this->getJson(self::URL)->assertJsonPath('data.plans.0.name', 'Negocio');

        // Guardar un plan (evento saved) invalida la caché.
        $this->makePlan(['name' => 'Profesional', 'slug' => 'profesional', 'sort_order' => 2]);
        $res = $this->getJson(self::URL);
        $res->assertJsonCount(2, 'data.plans')
            ->assertJsonPath('data.plans.0.name', 'Renombrado');
    }

    public function test_is_rate_limited(): void
    {
        $this->makePlan();

        for ($i = 0; $i < 60; $i++) {
            $this->getJson(self::URL)->assertOk();
        }

        $this->getJson(self::URL)->assertStatus(429);
    }
}
```

- [ ] **Step 2: Verificar que falla**

Run: `cd backend && php artisan test --filter=PublicLandingTest`
Expected: FAIL (404 en la ruta).

- [ ] **Step 3: Servicio de caché**

`backend/app/Services/Landing/PublicLandingCache.php`:

```php
<?php

namespace App\Services\Landing;

use Illuminate\Support\Facades\Cache;

/**
 * Caché de la respuesta pública de la landing (planes + textos de precios).
 * Se invalida al guardar/eliminar un plan y al guardar los textos desde Filament.
 */
final class PublicLandingCache
{
    public const KEY = 'landing:public';

    public const TTL_SECONDS = 300;

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
```

- [ ] **Step 4: Controlador**

`backend/app/Http/Controllers/Api/V1/PublicLandingController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Billing\Plan;
use App\Services\Landing\PublicLandingCache;
use App\Services\Settings\PricingContentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Datos públicos que consume la landing (Next.js): planes activos con precio
 * y textos editoriales de la sección de precios. Sin autenticación.
 */
class PublicLandingController extends ApiController
{
    public function show(): JsonResponse
    {
        $data = Cache::remember(PublicLandingCache::KEY, PublicLandingCache::TTL_SECONDS, fn () => [
            'plans' => $this->plans(),
            'pricing_content' => $this->pricingContent(),
        ]);

        return $this->success($data);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plans(): array
    {
        return Plan::active()
            ->where('price_monthly', '>', 0)
            ->ordered()
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'currency' => $plan->currency ?: 'USD',
                'is_featured' => (bool) $plan->is_featured,
                'yearly_savings_percent' => (int) $plan->getYearlySavingsPercent(),
                'features_list' => $plan->getFeaturesList(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingContent(): array
    {
        try {
            return app(PricingContentSettings::class)->all();
        } catch (\Throwable) {
            return array_map(fn (array $d) => $d['default'], PricingContentSettings::definitions());
        }
    }
}
```

- [ ] **Step 5: Ruta pública**

En `backend/routes/api.php`, añadir el `use` junto a los demás controladores:

```php
use App\Http\Controllers\Api\V1\PublicLandingController;
```

y dentro del bloque `// Public routes (no authentication required)`, después de `auth/reset-password`:

```php
    // Landing pública (planes + textos de precios), sin autenticación.
    Route::get('public/landing', [PublicLandingController::class, 'show'])
        ->middleware('throttle:60,1');
```

- [ ] **Step 6: Invalidación desde el modelo Plan y desde PricingContentSettings**

En `backend/app/Models/Billing/Plan.php`, añadir el import `use App\Services\Landing\PublicLandingCache;` y, dentro de la clase (antes de `scopeActive`):

```php
    protected static function booted(): void
    {
        $forgetLanding = static fn () => PublicLandingCache::forget();

        static::saved($forgetLanding);
        static::deleted($forgetLanding);
    }
```

En `backend/app/Services/Settings/PricingContentSettings.php`, añadir `use App\Services\Landing\PublicLandingCache;` y en `save()`, justo después de `Cache::forget(self::CACHE_KEY);`:

```php
        PublicLandingCache::forget();
```

- [ ] **Step 7: Eliminar ExampleTest y ejecutar**

```bash
cd backend
git rm tests/Feature/ExampleTest.php
vendor/bin/pint app/Http/Controllers/Api/V1/PublicLandingController.php app/Services/Landing/PublicLandingCache.php app/Models/Billing/Plan.php app/Services/Settings/PricingContentSettings.php tests/Feature/PublicLandingTest.php
php artisan test --filter=PublicLandingTest
```

Expected: 4 tests PASS.

- [ ] **Step 8: Commit**

```bash
git add backend/app/Services/Landing/PublicLandingCache.php backend/app/Http/Controllers/Api/V1/PublicLandingController.php backend/routes/api.php backend/app/Models/Billing/Plan.php backend/app/Services/Settings/PricingContentSettings.php backend/tests/Feature/PublicLandingTest.php backend/tests/Feature/ExampleTest.php
git commit -m "feat(api): endpoint público de landing con planes y textos de precios cacheados"
```

---

### Task 2: Mover el dashboard de `/` a `/dashboard`

**Files:**
- Move: `frontend/src/app/(panel)/page.tsx` → `frontend/src/app/(panel)/dashboard/page.tsx`
- Modify: `frontend/src/app/(auth)/actions.ts` (2× `redirect("/")`), `frontend/src/app/onboarding/onboarding-wizard.tsx` (2× `router.push("/")`), `frontend/src/components/panel/sidebar-nav.ts` (`href: "/"`), `frontend/src/components/panel/sidebar-content.tsx:11` (activo de la raíz)
- Test: `frontend/tests/unit/sidebar-nav.test.ts`

**Interfaces:**
- Produces: ruta `/dashboard` (protegida por `(panel)/layout.tsx`). La raíz `/` queda libre para la landing.

- [ ] **Step 1: Test que falla**

`frontend/tests/unit/sidebar-nav.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import { navGroups } from "@/components/panel/sidebar-nav";

describe("sidebar-nav", () => {
  it("apunta el Dashboard a /dashboard porque la raíz es la landing", () => {
    const dashboard = navGroups
      .flatMap((group) => group.items)
      .find((item) => item.label === "Dashboard");
    expect(dashboard?.href).toBe("/dashboard");
  });

  it("no tiene ningún destino en la raíz", () => {
    const roots = navGroups.flatMap((g) => g.items).filter((i) => i.href === "/");
    expect(roots).toHaveLength(0);
  });
});
```

Run: `pnpm test -- sidebar-nav`
Expected: FAIL (`"/"` ≠ `"/dashboard"`).

- [ ] **Step 2: Mover la página y actualizar referencias**

```bash
cd frontend
mkdir -p "src/app/(panel)/dashboard"
git mv "src/app/(panel)/page.tsx" "src/app/(panel)/dashboard/page.tsx"
sed -i '' 's|redirect("/");|redirect("/dashboard");|g' "src/app/(auth)/actions.ts"
sed -i '' 's|router.push("/")|router.push("/dashboard")|g' src/app/onboarding/onboarding-wizard.tsx
sed -i '' 's|{ label: "Dashboard", href: "/", icon: LayoutDashboard }|{ label: "Dashboard", href: "/dashboard", icon: LayoutDashboard }|' src/components/panel/sidebar-nav.ts
grep -rn 'href === "/"\|pathname === "/"' src/components/panel
```

El `grep` encuentra la comparación con la raíz en `src/components/panel/sidebar-content.tsx` (`if (href === "/") return currentPath === "/";`). Cambiarla al nuevo destino:

```bash
sed -i '' 's|if (href === "/") return currentPath === "/";|if (href === "/dashboard") return currentPath === "/dashboard";|' src/components/panel/sidebar-content.tsx
```

- [ ] **Step 3: Verificar**

Run: `pnpm test -- sidebar-nav && pnpm typecheck && pnpm lint`
Expected: tests PASS, sin errores de tipos ni lint. Además `grep -rn 'redirect("/")\|push("/")' src` no devuelve nada.

- [ ] **Step 4: Commit**

```bash
git add -A frontend/src frontend/tests/unit/sidebar-nav.test.ts
git commit -m "refactor(panel): mover el dashboard a /dashboard para liberar la raíz a la landing"
```

---

### Task 3: Los formularios de login/registro conservan lo escrito al fallar

Contexto: en React 19, `<form action={…}>` resetea los inputs no controlados cuando termina la acción, incluso si devolvió errores. Hoy, una contraseña rechazada por el backend vacía todo el formulario de registro. Solución: la acción devuelve `values` (nunca contraseñas) y los inputs usan `defaultValue` desde el estado.

**Files:**
- Modify: `frontend/src/app/(auth)/actions.ts`, `frontend/src/app/(auth)/register/register-form.tsx`, `frontend/src/app/(auth)/login/login-form.tsx`, `frontend/src/app/(auth)/register/page.tsx`, `frontend/src/app/(auth)/layout.tsx`
- Test: `frontend/tests/unit/register-form.test.tsx`

**Interfaces:**
- `AuthState` gana `values?: Record<string, string>`.

- [ ] **Step 1: Test que falla**

`frontend/tests/unit/register-form.test.tsx`:

```tsx
import { act, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/app/(auth)/actions", () => ({
  registerAction: vi.fn(async (_prev: unknown, formData: FormData) => ({
    ok: false,
    message: "La contraseña debe tener una mayúscula",
    fieldErrors: { password: ["La contraseña debe tener una mayúscula"] },
    values: {
      name: String(formData.get("name") ?? ""),
      company_name: String(formData.get("company_name") ?? ""),
      email: String(formData.get("email") ?? ""),
      terms: String(formData.get("terms") ?? ""),
    },
  })),
}));

import { RegisterForm } from "@/app/(auth)/register/register-form";

describe("RegisterForm", () => {
  it("conserva nombre, empresa, correo y términos cuando la acción falla", async () => {
    render(<RegisterForm />);

    const name = screen.getByLabelText("Tu nombre") as HTMLInputElement;
    const company = screen.getByLabelText("Nombre de empresa") as HTMLInputElement;
    const email = screen.getByLabelText("Correo electrónico") as HTMLInputElement;
    const terms = screen.getByRole("checkbox") as HTMLInputElement;
    const password = screen.getByLabelText("Contraseña") as HTMLInputElement;

    name.value = "Ana Pérez";
    company.value = "Andina S.A.";
    email.value = "ana@andina.ec";
    terms.checked = true;
    password.value = "malaclave";

    await act(async () => {
      screen.getByRole("button", { name: "Crear cuenta" }).closest("form")!.requestSubmit();
    });

    expect(await screen.findByText("La contraseña debe tener una mayúscula", { selector: "p.text-xs" })).toBeInTheDocument();
    expect(name.value).toBe("Ana Pérez");
    expect(company.value).toBe("Andina S.A.");
    expect(email.value).toBe("ana@andina.ec");
    expect(terms.checked).toBe(true);
    expect(password.value).toBe("");
  });
});
```

Run: `pnpm test -- register-form`
Expected: FAIL (los inputs quedan vacíos tras la acción).

- [ ] **Step 2: Devolver `values` desde las acciones**

En `frontend/src/app/(auth)/actions.ts`:

Cambiar el tipo:

```ts
export type AuthState = {
  ok: boolean;
  message?: string;
  fieldErrors?: Record<string, string[]>;
  /** Valores a conservar en el formulario tras un error (nunca contraseñas). */
  values?: Record<string, string>;
} | null;
```

Añadir después de los esquemas zod:

```ts
/** Extrae campos de texto del FormData para repoblar el formulario tras un error. */
function keepValues(formData: FormData, keys: string[]): Record<string, string> {
  const values: Record<string, string> = {};
  for (const key of keys) {
    const value = formData.get(key);
    if (typeof value === "string") values[key] = value;
  }
  return values;
}

const LOGIN_KEEP = ["email"];
const REGISTER_KEEP = ["name", "company_name", "email", "terms"];
```

En `loginAction`, en los tres `return { ok: false, … }`, añadir `values: keepValues(formData, LOGIN_KEEP),`. En `registerAction`, en sus tres `return { ok: false, … }`, añadir `values: keepValues(formData, REGISTER_KEEP),`.

- [ ] **Step 3: `defaultValue` en los formularios**

En `register-form.tsx`, añadir `const values = state?.values ?? {};` justo después de `useActionState`, y en los inputs:

```tsx
<Input id="name" name="name" required autoComplete="name" defaultValue={values.name ?? ""} />
<Input id="company_name" name="company_name" required defaultValue={values.company_name ?? ""} />
<Input id="email" name="email" type="email" required autoComplete="email" defaultValue={values.email ?? ""} />
```

y en el checkbox de términos: `defaultChecked={values.terms === "on"}`.

En `login-form.tsx`, añadir `const values = state?.values ?? {};` y en el input de email `defaultValue={values.email ?? ""}`.

- [ ] **Step 4: Copy correcto en registro y layout de auth**

En `register/page.tsx`: `Empieza a facturar en minutos. 14 días de prueba gratis.` → `Empieza a facturar en minutos.`

En `(auth)/layout.tsx`: `Autorizado por el Servicio de Rentas Internas` → `Comprobantes autorizados por el SRI`; los dos textos `AmePhia Facturación` del logo → `Facturón`.

- [ ] **Step 5: Verificar**

Run: `pnpm test -- register-form && pnpm typecheck && pnpm lint`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add "frontend/src/app/(auth)" frontend/tests/unit/register-form.test.tsx
git commit -m "fix(auth): conservar lo escrito en login/registro cuando la acción falla y corregir copy"
```

---
### Task 4: Módulos puros — `config.ts`, `pricing.ts` y contenido `landing.ts`

**Files:**
- Create: `frontend/src/lib/landing/config.ts`, `frontend/src/lib/landing/pricing.ts`, `frontend/src/content/landing.ts`
- Test: `frontend/tests/unit/config.test.ts`, `frontend/tests/unit/pricing.test.ts`, `frontend/tests/unit/content.test.ts`

**Interfaces:**
- `config.ts`: `APP_URL: string`, `WHATSAPP_DIGITS: string`, `CONTACT_EMAIL: string`, `STORE_PLAY_URL: string`, `STORE_APPSTORE_URL: string`, `WHATSAPP_DEFAULT_TEXT`, `digitsOnly(value: string): string`, `whatsappUrl(text?: string, digits?: string): string`, `formatWhatsapp(digits?: string): string`.
- `pricing.ts`: tipos `LandingPlan`, `PricingContent`, `LandingData`; `formatPrice(amount: number, currency?: string): string`, `cheapestPlan(plans: LandingPlan[]): LandingPlan | null`, `maxSavings(plans: LandingPlan[]): number`, `monthlyEquivalent(priceYearly: number): number`.
- `landing.ts`: `NAV_LINKS`, `HERO`, `TRUST_CHIPS`, `DOCUMENT_TYPES`, `SHOWCASE`, `FEATURES`, `FEATURE_LIST_FOR_SEO`, `STEPS`, `ARBITROS`, `REASONS`, `FAQS`, `FINAL_CTA`, `SITE_DESCRIPTION`; tipos `FaqItem`, `DocumentType`, `Feature`, `IconName`, `ShowcaseRow`.

- [ ] **Step 1: Tests que fallan**

`frontend/tests/unit/config.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import {
  CONTACT_EMAIL,
  STORE_APPSTORE_URL,
  STORE_PLAY_URL,
  WHATSAPP_DIGITS,
  digitsOnly,
  formatWhatsapp,
  whatsappUrl,
} from "@/lib/landing/config";

describe("config", () => {
  it("tiene los defaults acordados", () => {
    expect(WHATSAPP_DIGITS).toBe("13347324056");
    expect(CONTACT_EMAIL).toBe("info@amephia.com");
    expect(STORE_PLAY_URL).toBe("");
    expect(STORE_APPSTORE_URL).toBe("");
  });

  it("deja solo dígitos", () => {
    expect(digitsOnly("+1 (334) 732-4056")).toBe("13347324056");
  });

  it("arma la URL de WhatsApp con texto codificado", () => {
    expect(whatsappUrl("Hola, quiero info")).toBe(
      "https://wa.me/13347324056?text=Hola%2C%20quiero%20info",
    );
  });

  it("formatea números de EE. UU. y Ecuador", () => {
    expect(formatWhatsapp("13347324056")).toBe("+1 334 732 4056");
    expect(formatWhatsapp("593991234567")).toBe("+593 99 123 4567");
    expect(formatWhatsapp("4412345678")).toBe("+4412345678");
  });
});
```

`frontend/tests/unit/pricing.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import {
  cheapestPlan,
  formatPrice,
  maxSavings,
  monthlyEquivalent,
  type LandingPlan,
} from "@/lib/landing/pricing";

const plan = (over: Partial<LandingPlan>): LandingPlan => ({
  id: 1,
  name: "Plan",
  slug: "plan",
  description: "",
  price_monthly: 7.99,
  price_yearly: 79.9,
  currency: "USD",
  is_featured: false,
  yearly_savings_percent: 17,
  features_list: [],
  ...over,
});

describe("pricing", () => {
  it("formatea en dólares con dos decimales", () => {
    expect(formatPrice(2.99)).toBe("$2.99");
    expect(formatPrice(499)).toBe("$499.00");
  });

  it("encuentra el plan más barato", () => {
    const plans = [plan({ slug: "b", price_monthly: 7.99 }), plan({ slug: "a", price_monthly: 2.99 })];
    expect(cheapestPlan(plans)?.slug).toBe("a");
    expect(cheapestPlan([])).toBeNull();
  });

  it("calcula el ahorro máximo y el equivalente mensual", () => {
    expect(maxSavings([plan({ yearly_savings_percent: 10 }), plan({ yearly_savings_percent: 17 })])).toBe(17);
    expect(maxSavings([])).toBe(0);
    expect(monthlyEquivalent(29.9)).toBe(2.49);
  });
});
```

`frontend/tests/unit/content.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import {
  ARBITROS,
  DOCUMENT_TYPES,
  FAQS,
  FEATURES,
  HERO,
  REASONS,
  STEPS,
  TRUST_CHIPS,
} from "@/content/landing";

function allStrings(value: unknown): string[] {
  if (typeof value === "string") return [value];
  if (Array.isArray(value)) return value.flatMap(allStrings);
  if (value && typeof value === "object") return Object.values(value).flatMap(allStrings);
  return [];
}

describe("contenido de la landing", () => {
  it("tiene 13 preguntas frecuentes completas", () => {
    expect(FAQS).toHaveLength(13);
    for (const f of FAQS) {
      expect(f.q.length).toBeGreaterThan(10);
      expect(f.a.length).toBeGreaterThan(40);
    }
  });

  it("lista los 6 comprobantes del SRI con su código", () => {
    expect(DOCUMENT_TYPES.map((d) => d.code)).toEqual(["01", "03", "04", "05", "06", "07"]);
  });

  it("tiene 12 funcionalidades, 3 pasos y 3 razones", () => {
    expect(FEATURES).toHaveLength(12);
    expect(STEPS).toHaveLength(3);
    expect(REASONS).toHaveLength(3);
    expect(TRUST_CHIPS.length).toBeGreaterThanOrEqual(5);
    expect(ARBITROS.bullets).toHaveLength(4);
  });

  it("no promete pruebas gratis ni afirma que el software esté autorizado por el SRI", () => {
    const text = allStrings({ HERO, FAQS, FEATURES, REASONS, STEPS, ARBITROS, TRUST_CHIPS }).join("\n");
    expect(text).not.toMatch(/prueba gratis|gratis/i);
    expect(text).not.toMatch(/(software|sistema|plataforma) autorizad[oa] por el SRI/i);
  });
});
```

Run: `pnpm test`
Expected: FAIL (módulos inexistentes).

- [ ] **Step 2: `config.ts`**

`frontend/src/lib/landing/config.ts`:

```ts
/**
 * Configuración de contacto y tiendas de la landing. Las NEXT_PUBLIC_* se
 * incrustan en build; si faltan, valen los defaults acordados.
 */
export function digitsOnly(value: string): string {
  return value.replace(/\D+/g, "");
}

export const APP_URL = (process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000").replace(/\/+$/, "");
export const WHATSAPP_DIGITS = digitsOnly(process.env.NEXT_PUBLIC_WHATSAPP ?? "13347324056");
export const CONTACT_EMAIL = process.env.NEXT_PUBLIC_CONTACT_EMAIL ?? "info@amephia.com";
export const STORE_PLAY_URL = process.env.NEXT_PUBLIC_STORE_PLAY_URL ?? "";
export const STORE_APPSTORE_URL = process.env.NEXT_PUBLIC_STORE_APPSTORE_URL ?? "";
export const WHATSAPP_DEFAULT_TEXT = "Hola, quiero información sobre Facturón";

export function whatsappUrl(text: string = WHATSAPP_DEFAULT_TEXT, digits: string = WHATSAPP_DIGITS): string {
  return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`;
}

/** "+1 334 732 4056" / "+593 99 123 4567"; otros países: "+<dígitos>". */
export function formatWhatsapp(digits: string = WHATSAPP_DIGITS): string {
  if (digits.length === 11 && digits.startsWith("1")) {
    return `+1 ${digits.slice(1, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
  }
  if (digits.length === 12 && digits.startsWith("593")) {
    return `+593 ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8)}`;
  }
  return `+${digits}`;
}
```

- [ ] **Step 3: `pricing.ts`**

`frontend/src/lib/landing/pricing.ts`:

```ts
export type LandingPlan = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  price_monthly: number;
  price_yearly: number;
  currency: string;
  is_featured: boolean;
  yearly_savings_percent: number;
  features_list: string[];
};

export type PricingContent = {
  eyebrow: string;
  title: string;
  subtitle: string;
  badge_enabled: boolean;
  badge_text: string;
  footer_note: string;
};

export type LandingData = {
  plans: LandingPlan[];
  pricing_content: PricingContent;
};

export function formatPrice(amount: number, currency = "USD"): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
}

export function cheapestPlan(plans: LandingPlan[]): LandingPlan | null {
  return plans.reduce<LandingPlan | null>(
    (min, plan) => (min === null || plan.price_monthly < min.price_monthly ? plan : min),
    null,
  );
}

export function maxSavings(plans: LandingPlan[]): number {
  return plans.reduce((max, plan) => Math.max(max, plan.yearly_savings_percent), 0);
}

export function monthlyEquivalent(priceYearly: number): number {
  return Math.round((priceYearly / 12) * 100) / 100;
}
```

- [ ] **Step 4: Contenido `landing.ts`**

`frontend/src/content/landing.ts`:

```ts
/**
 * Única fuente del copy y los datos estáticos de la landing. La UI y el
 * JSON-LD leen de aquí. Sin JSX: debe poder importarse en Node (tests, llms.txt).
 */
export type IconName =
  | "ShieldCheck"
  | "Zap"
  | "Mail"
  | "Store"
  | "Package"
  | "BookOpen"
  | "Users"
  | "Building2"
  | "Code2"
  | "Smartphone"
  | "Repeat"
  | "Sparkles"
  | "FileText"
  | "FileMinus"
  | "FilePlus2"
  | "Truck"
  | "Receipt"
  | "FileCheck2";

export type FaqItem = { q: string; a: string };
export type DocumentType = { code: string; name: string; use: string; icon: IconName };
export type Feature = { title: string; text: string; icon: IconName; span?: 2 };
export type ShowcaseRow = {
  title: string;
  text: string;
  bullets: string[];
  image: { src: string; alt: string; width: number; height: number; kind: "browser" | "phone" };
};

export const NAV_LINKS = [
  { href: "#funcionalidades", label: "Funcionalidades" },
  { href: "#como-funciona", label: "Cómo funciona" },
  { href: "#precios", label: "Precios" },
  { href: "#faq", label: "FAQ" },
] as const;

export const SITE_DESCRIPTION =
  "Factura electrónicamente en Ecuador desde $2.99 al mes, sin comisión por documento. Facturas, retenciones y guías autorizadas por el SRI en segundos.";

export const HERO = {
  eyebrow: "Facturación electrónica · Ecuador",
  title: "Facturación electrónica que el SRI autoriza en segundos",
  subtitle:
    "Facturas, retenciones, guías y más, firmadas con tu certificado y enviadas a tus clientes automáticamente.",
} as const;

export const TRUST_CHIPS = [
  "Ficha técnica del SRI",
  "Firma XAdES-BES",
  "Certificados BCE, Security Data y ANF",
  "Ambiente de pruebas y producción",
  "Todas las tarifas de IVA del catálogo SRI (15 %, 8 %, 5 %, 0 %, exento, no objeto)",
] as const;

export const DOCUMENT_TYPES: DocumentType[] = [
  { code: "01", name: "Factura", use: "Ventas de bienes y servicios a consumidores y empresas.", icon: "FileText" },
  { code: "03", name: "Liquidación de compra", use: "Compras a personas que no emiten comprobantes.", icon: "Receipt" },
  { code: "04", name: "Nota de crédito", use: "Devoluciones, descuentos y anulaciones parciales.", icon: "FileMinus" },
  { code: "05", name: "Nota de débito", use: "Cargos e intereses posteriores a la factura.", icon: "FilePlus2" },
  { code: "06", name: "Guía de remisión", use: "Traslado de mercadería con respaldo tributario.", icon: "Truck" },
  { code: "07", name: "Comprobante de retención", use: "Retenciones de IVA y renta a tus proveedores.", icon: "FileCheck2" },
];

export const SHOWCASE: ShowcaseRow[] = [
  {
    title: "Todo tu negocio en un panel",
    text: "Ventas del mes, documentos pendientes y rechazados, cobros y el estado de cada comprobante ante el SRI, sin recargar la página.",
    bullets: ["Estado SRI de cada documento en vivo", "Cobros y pendientes de un vistazo", "Varias empresas desde una sola cuenta"],
    image: { src: "/marketing/panel-dashboard.png", alt: "Panel web de Facturón con el resumen de ventas y documentos", width: 1920, height: 1200, kind: "browser" },
  },
  {
    title: "Una factura en 30 segundos",
    text: "Busca al cliente por RUC o cédula y completa sus datos desde el SRI, agrega productos con IVA calculado y emite. Nosotros firmamos, enviamos y entregamos.",
    bullets: ["Cliente por RUC o cédula con datos del SRI", "IVA y totales calculados solos", "Firma y envío automáticos"],
    image: { src: "/marketing/panel-invoice.png", alt: "Formulario de nueva factura en Facturón", width: 1920, height: 1200, kind: "browser" },
  },
  {
    title: "Vende desde el celular o en caja",
    text: "La app para Android e iOS emite y consulta comprobantes desde donde estés. El punto de venta abre sesiones de caja e imprime en impresora térmica.",
    bullets: ["App Android e iOS", "Sesiones de caja y cierres", "Impresión térmica del recibo"],
    image: { src: "/marketing/app-home.png", alt: "Inicio de la app móvil de Facturón con el resumen del mes", width: 1080, height: 2400, kind: "phone" },
  },
];

export const FEATURES: Feature[] = [
  { title: "Firmamos por ti", text: "Sube tu certificado .p12 una vez y firmamos cada comprobante con XAdES-BES, como exige el SRI. No instalas nada.", icon: "ShieldCheck", span: 2 },
  { title: "Autorización con reintentos", text: "Si el SRI no responde, el documento espera en cola y se reintenta solo. Ves el estado en tiempo real.", icon: "Zap", span: 2 },
  { title: "RIDE + XML por correo", text: "Tu cliente recibe el PDF y el XML al instante, con tu logo.", icon: "Mail" },
  { title: "Punto de venta", text: "Sesiones de caja, factura al instante e impresora térmica.", icon: "Store" },
  { title: "Inventario y compras", text: "Stock con alertas de mínimos, compras y documentos recibidos.", icon: "Package" },
  { title: "Contabilidad, ATS e IVA", text: "Plan de cuentas, asientos automáticos, ATS mensual y resumen para la declaración de IVA.", icon: "BookOpen" },
  { title: "Portal de clientes", text: "Tus clientes descargan sus comprobantes sin pedírtelos.", icon: "Users" },
  { title: "Multi-empresa", text: "Varios RUC en una cuenta; cambias de empresa con un clic.", icon: "Building2" },
  { title: "API REST", text: "Crea documentos y consulta datos desde tu propio sistema.", icon: "Code2" },
  { title: "App móvil", text: "Android e iOS para emitir y consultar desde cualquier lugar.", icon: "Smartphone" },
  { title: "Proformas y recurrentes", text: "Cotiza, convierte en factura y programa cobros periódicos.", icon: "Repeat" },
  { title: "Categorización con IA", text: "Clasifica productos y gastos automáticamente.", icon: "Sparkles" },
];

export const FEATURE_LIST_FOR_SEO = FEATURES.map((f) => f.title);

export const STEPS = [
  { title: "Crea tu cuenta con tu RUC", text: "Registro en 2 minutos. Configura tu establecimiento y punto de emisión." },
  { title: "Sube tu certificado .p12", text: "El del BCE, Security Data, ANF u otra entidad acreditada. Lo usamos para firmar por ti." },
  { title: "Emite", text: "Se firma, se envía al SRI y llega a tu cliente en PDF y XML. Automático." },
] as const;

export const ARBITROS = {
  eyebrow: "Facturón para Árbitros",
  title: "Una factura por partido, sin perder ninguno.",
  text: "Módulo especializado para árbitros de fútbol que facturan a la FEF.",
  bullets: [
    "Partidos pendientes por facturar en tiempo real",
    "Facturación en lote: una factura por partido con el concepto que exige la FEF",
    "Control de la ventana de recepción (del 1 al 20 de cada mes)",
    "Si anulas una factura, el partido vuelve a pendiente automáticamente",
  ],
  cta: "Escríbenos por WhatsApp",
  whatsappText: "Hola, soy árbitro y quiero información de Facturón",
} as const;

export const REASONS = [
  { title: "Hecho para el SRI desde cero", text: "XML según la ficha técnica, firma XAdES-BES y comunicación directa con los web services del SRI.", icon: "ShieldCheck" as IconName },
  { title: "Todo en uno", text: "Facturación, punto de venta, inventario, contabilidad básica y portal de clientes, sin integrar cinco sistemas.", icon: "Building2" as IconName },
  { title: "Sin trabajo manual", text: "Creas el documento; nosotros lo firmamos, lo enviamos al SRI y se lo mandamos a tu cliente.", icon: "Zap" as IconName },
] as const;

export const FAQS: FaqItem[] = [
  {
    q: "¿Qué necesito para facturar electrónicamente en Ecuador?",
    a: "Tu RUC activo, una firma electrónica vigente (archivo .p12 del Banco Central, Security Data, ANF u otra entidad acreditada) y estar habilitado para emitir comprobantes electrónicos en SRI en línea. Facturón te guía paso a paso en el proceso.",
  },
  {
    q: "¿Los comprobantes emitidos con Facturón son válidos ante el SRI?",
    a: "Sí. Generamos el XML según la ficha técnica del SRI, lo firmamos con XAdES-BES y lo enviamos a los web services del SRI, que devuelve la autorización de cada comprobante. Facturón no es el SRI ni está afiliado a él.",
  },
  {
    q: "¿Cuánto cuesta?",
    a: "Los planes empiezan en $2.99 al mes y ninguno cobra comisión por documento. El plan anual tiene descuento. Puedes ver todos los planes y lo que incluye cada uno en la tabla de precios.",
  },
  {
    q: "¿Cómo funciona el registro y el pago?",
    a: "Te registras en 2 minutos, eliges el plan y pagas por transferencia bancaria. Cuando confirmamos el pago, tu cuenta se activa con todas las funciones del plan. Hoy no ofrecemos período de prueba.",
  },
  {
    q: "¿Puedo migrar desde otro sistema de facturación?",
    a: "Sí. Importas tus clientes y productos desde CSV o Excel, y tus secuenciales continúan donde los dejaste en el sistema anterior, así no rompes la numeración ante el SRI.",
  },
  {
    q: "¿Qué pasa si el SRI está caído o no responde?",
    a: "Tu documento queda en cola y se reintenta automáticamente hasta que el servicio se restablece. Puedes ver el estado de cada comprobante en tiempo real desde el panel.",
  },
  {
    q: "¿Puedo facturar desde el celular?",
    a: "Sí. La app de Facturón está disponible para Android en Google Play y la versión para iOS está en revisión en la App Store. La web también funciona en el navegador del celular.",
  },
  {
    q: "¿Genera el ATS y la declaración de IVA?",
    a: "Sí. Desde reportes generas el ATS mensual en XML, listo para subir al portal del SRI, y un resumen de ventas e IVA para preparar la declaración.",
  },
  {
    q: "¿Sirve para mi contador?",
    a: "Sí. Puedes dar acceso a tu contador, y tiene plan de cuentas, asientos automáticos y reportes de ventas, compras y retenciones para trabajar sin pedirte archivos.",
  },
  {
    q: "¿Tiene API para conectar mi propio sistema?",
    a: "Sí. Facturón tiene una API REST documentada para crear documentos y consultar clientes, productos y reportes. Está disponible según el plan que elijas.",
  },
  {
    q: "¿Puedo manejar varias empresas (RUC) con una sola cuenta?",
    a: "Sí, en los planes que lo incluyen. Administras varios RUC desde la misma cuenta y cambias de empresa con un clic, sin pagar otra suscripción por cada una.",
  },
  {
    q: "¿Venden certificados de firma electrónica?",
    a: "No. Usas el certificado .p12 que ya tienes o el que compres en el Banco Central, Security Data, ANF u otra entidad acreditada. Lo subes una vez y firmamos con él.",
  },
  {
    q: "¿Qué es Facturón para Árbitros?",
    a: "Es un módulo para árbitros de fútbol que facturan a la FEF: muestra los partidos pendientes por facturar, emite una factura por partido con el concepto exigido, controla la ventana de recepción y devuelve el partido a pendiente si anulas la factura.",
  },
];

export const FINAL_CTA = {
  title: "Empieza a facturar hoy.",
  text: "Crea tu cuenta en 2 minutos. Sin contratos. Desde $2.99 al mes.",
} as const;
```

- [ ] **Step 5: Verificar**

Run: `pnpm test && pnpm typecheck`
Expected: todos PASS.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/lib/landing/config.ts frontend/src/lib/landing/pricing.ts frontend/src/content/landing.ts frontend/tests/unit/config.test.ts frontend/tests/unit/pricing.test.ts frontend/tests/unit/content.test.ts
git commit -m "feat(landing): configuración, utilidades de precios y contenido con pruebas"
```

---

### Task 5: Clave de acceso, máquina de estados de la demo y hook

**Files:**
- Create: `frontend/src/lib/landing/clave-acceso.ts`, `frontend/src/lib/landing/demo-machine.ts`, `frontend/src/lib/landing/use-demo-sequence.ts`
- Test: `frontend/tests/unit/clave-acceso.test.ts`, `frontend/tests/unit/demo-machine.test.ts`, `frontend/tests/unit/use-demo-sequence.test.tsx`

**Interfaces:**
- `modulo11(digits: string): number`; `claveAcceso(input: ClaveAccesoInput): string` (49 dígitos) con `ClaveAccesoInput = { date: Date; ruc: string; sequential: string; docType?: string; environment?: "1" | "2"; series?: string; numericCode?: string; emissionType?: "1" }`.
- `DEMO_STEPS`, `DemoStep`, `DEMO_DURATIONS_MS`, `DEMO_CYCLE_MS`, `nextStep(step)`, `stepIndex(step)`, `isAtLeast(step, target)`, `DEMO_INVOICE`.
- `useDemoSequence(running: boolean, initial?: DemoStep): DemoStep`.

- [ ] **Step 1: Tests que fallan**

`frontend/tests/unit/clave-acceso.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import { claveAcceso, modulo11 } from "@/lib/landing/clave-acceso";

describe("modulo11", () => {
  it("aplica pesos 2..7 desde la derecha y 11 - (suma mod 11)", () => {
    expect(modulo11("1")).toBe(9); // 1*2=2 → 11-2
    expect(modulo11("10")).toBe(8); // 0*2+1*3=3 → 11-3
  });

  it("mapea 11 → 0 y 10 → 1", () => {
    expect(modulo11("0")).toBe(0); // suma 0 → 11 → 0
    expect(modulo11("6")).toBe(1); // 6*2=12 → 12 mod 11 = 1 → 10 → 1
  });

  it("rechaza no dígitos", () => {
    expect(() => modulo11("12a")).toThrow();
  });
});

describe("claveAcceso", () => {
  const clave = claveAcceso({ date: new Date(2026, 8, 12), ruc: "1790012345001", sequential: "123" });

  it("tiene 49 dígitos con la estructura del SRI", () => {
    expect(clave).toMatch(/^\d{49}$/);
    expect(clave.slice(0, 8)).toBe("12092026"); // ddmmaaaa
    expect(clave.slice(8, 10)).toBe("01"); // factura
    expect(clave.slice(10, 23)).toBe("1790012345001"); // RUC
    expect(clave.slice(23, 24)).toBe("2"); // producción
    expect(clave.slice(24, 30)).toBe("001001"); // serie
    expect(clave.slice(30, 39)).toBe("000000123"); // secuencial
    expect(clave.slice(47, 48)).toBe("1"); // emisión normal
  });

  it("termina con el dígito verificador de los primeros 48", () => {
    expect(Number(clave[48])).toBe(modulo11(clave.slice(0, 48)));
  });

  it("falla si el cuerpo no mide 48 dígitos", () => {
    expect(() => claveAcceso({ date: new Date(2026, 8, 12), ruc: "123", sequential: "1" })).toThrow(/48/);
  });
});
```

`frontend/tests/unit/demo-machine.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import {
  DEMO_CYCLE_MS,
  DEMO_DURATIONS_MS,
  DEMO_INVOICE,
  DEMO_STEPS,
  isAtLeast,
  nextStep,
} from "@/lib/landing/demo-machine";

describe("demo-machine", () => {
  it("recorre los 5 pasos y vuelve al inicio", () => {
    expect(DEMO_STEPS).toEqual(["draft", "signing", "sending", "authorized", "delivered"]);
    expect(nextStep("draft")).toBe("signing");
    expect(nextStep("delivered")).toBe("draft");
  });

  it("dura 12,5 s por ciclo", () => {
    const total = Object.values(DEMO_DURATIONS_MS).reduce((a, b) => a + b, 0);
    expect(total).toBe(12_500);
    expect(DEMO_CYCLE_MS).toBe(total);
  });

  it("compara el avance", () => {
    expect(isAtLeast("authorized", "signing")).toBe(true);
    expect(isAtLeast("draft", "signing")).toBe(false);
    expect(isAtLeast("sending", "sending")).toBe(true);
  });

  it("los totales de la factura de ejemplo cuadran", () => {
    const subtotal = DEMO_INVOICE.lines.reduce((s, l) => s + l.total, 0);
    expect(subtotal).toBe(DEMO_INVOICE.subtotal);
    expect(DEMO_INVOICE.iva).toBeCloseTo(subtotal * (DEMO_INVOICE.ivaRate / 100), 2);
    expect(DEMO_INVOICE.total).toBeCloseTo(subtotal + DEMO_INVOICE.iva, 2);
  });
});
```

`frontend/tests/unit/use-demo-sequence.test.tsx`:

```tsx
import { act, renderHook } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useDemoSequence } from "@/lib/landing/use-demo-sequence";

describe("useDemoSequence", () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it("avanza según las duraciones cuando está corriendo", () => {
    const { result } = renderHook(() => useDemoSequence(true));
    expect(result.current).toBe("draft");
    act(() => vi.advanceTimersByTime(2500));
    expect(result.current).toBe("signing");
    act(() => vi.advanceTimersByTime(1800));
    expect(result.current).toBe("sending");
  });

  it("no avanza si no está corriendo y respeta el paso inicial", () => {
    const { result } = renderHook(() => useDemoSequence(false, "authorized"));
    act(() => vi.advanceTimersByTime(20_000));
    expect(result.current).toBe("authorized");
  });
});
```

Run: `pnpm test`
Expected: FAIL (módulos inexistentes).

- [ ] **Step 2: `clave-acceso.ts`**

```ts
/**
 * Clave de acceso del SRI (49 dígitos) con dígito verificador módulo 11.
 * Solo para la demo de la landing: el formato es real, los datos son ficticios.
 */
export type ClaveAccesoInput = {
  date: Date;
  ruc: string;
  sequential: string;
  docType?: string;
  environment?: "1" | "2";
  series?: string;
  numericCode?: string;
  emissionType?: "1";
};

/** Pesos 2..7 cíclicos desde la derecha; 11 → 0, 10 → 1. */
export function modulo11(digits: string): number {
  if (!/^\d+$/.test(digits)) throw new Error("modulo11: solo acepta dígitos");
  let weight = 2;
  let sum = 0;
  for (let i = digits.length - 1; i >= 0; i--) {
    sum += Number(digits[i]) * weight;
    weight = weight === 7 ? 2 : weight + 1;
  }
  const result = 11 - (sum % 11);
  if (result === 11) return 0;
  if (result === 10) return 1;
  return result;
}

export function claveAcceso(input: ClaveAccesoInput): string {
  const d = input.date;
  const dd = String(d.getDate()).padStart(2, "0");
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const yyyy = String(d.getFullYear());
  const body =
    `${dd}${mm}${yyyy}` +
    (input.docType ?? "01") +
    input.ruc +
    (input.environment ?? "2") +
    (input.series ?? "001001") +
    input.sequential.padStart(9, "0") +
    (input.numericCode ?? "12345678") +
    (input.emissionType ?? "1");
  if (body.length !== 48) {
    throw new Error(`claveAcceso: el cuerpo tiene ${body.length} dígitos, se esperaban 48`);
  }
  return body + String(modulo11(body));
}
```

- [ ] **Step 3: `demo-machine.ts`**

```ts
/** Máquina de estados de la demo "Emisión en vivo" (sin React, testeable). */
export const DEMO_STEPS = ["draft", "signing", "sending", "authorized", "delivered"] as const;
export type DemoStep = (typeof DEMO_STEPS)[number];

export const DEMO_DURATIONS_MS: Record<DemoStep, number> = {
  draft: 2500,
  signing: 1800,
  sending: 2200,
  authorized: 4000,
  delivered: 2000,
};

export const DEMO_CYCLE_MS = Object.values(DEMO_DURATIONS_MS).reduce((a, b) => a + b, 0);

export function stepIndex(step: DemoStep): number {
  return DEMO_STEPS.indexOf(step);
}

export function nextStep(step: DemoStep): DemoStep {
  return DEMO_STEPS[(stepIndex(step) + 1) % DEMO_STEPS.length];
}

export function isAtLeast(step: DemoStep, target: DemoStep): boolean {
  return stepIndex(step) >= stepIndex(target);
}

export const DEMO_INVOICE = {
  number: "001-001-000000123",
  emitterRuc: "1791234567001",
  customer: {
    name: "Comercial Andina S.A.",
    ruc: "1790012345001",
    email: "facturacion@comercialandina.ec",
  },
  lines: [
    { description: "Servicio de consultoría", qty: 1, unit: 120, total: 120 },
    { description: "Licencia mensual", qty: 1, unit: 30, total: 30 },
  ],
  subtotal: 150,
  ivaRate: 15,
  iva: 22.5,
  total: 172.5,
} as const;
```

- [ ] **Step 4: `use-demo-sequence.ts`**

```ts
"use client";

import { useEffect, useState } from "react";
import { DEMO_DURATIONS_MS, nextStep, type DemoStep } from "./demo-machine";

/** Avanza la demo paso a paso mientras `running` sea true; se pausa si no. */
export function useDemoSequence(running: boolean, initial: DemoStep = "draft"): DemoStep {
  const [step, setStep] = useState<DemoStep>(initial);

  useEffect(() => {
    if (!running) return;
    const id = window.setTimeout(() => setStep((s) => nextStep(s)), DEMO_DURATIONS_MS[step]);
    return () => window.clearTimeout(id);
  }, [running, step]);

  return step;
}
```

- [ ] **Step 5: Verificar y commit**

Run: `pnpm test && pnpm typecheck`
Expected: PASS.

```bash
git add frontend/src/lib/landing/clave-acceso.ts frontend/src/lib/landing/demo-machine.ts frontend/src/lib/landing/use-demo-sequence.ts frontend/tests/unit/clave-acceso.test.ts frontend/tests/unit/demo-machine.test.ts frontend/tests/unit/use-demo-sequence.test.tsx
git commit -m "feat(landing): clave de acceso módulo 11 y máquina de estados de la demo"
```

---

### Task 6: Carga de datos (API/fixture) y JSON-LD

**Files:**
- Create: `frontend/src/lib/landing/fixture.ts`, `frontend/src/lib/landing/data.ts`, `frontend/src/lib/landing/jsonld.ts`
- Test: `frontend/tests/unit/data.test.ts`, `frontend/tests/unit/jsonld.test.ts`

**Interfaces:**
- `LANDING_FIXTURE: LandingData`.
- `getLandingData(fetcher?: typeof fetch): Promise<LandingData | null>`; `isLandingData(value: unknown): value is LandingData`; `LANDING_REVALIDATE_SECONDS = 300`.
- `buildJsonLd(input: { baseUrl: string; plans: LandingPlan[]; faqs: FaqItem[]; featureList: string[]; contactEmail: string; whatsappDigits: string; description: string }): { "@context": string; "@graph": unknown[] }`.

- [ ] **Step 1: Tests que fallan**

`frontend/tests/unit/data.test.ts`:

```ts
import { afterEach, describe, expect, it, vi } from "vitest";
import { getLandingData, isLandingData } from "@/lib/landing/data";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";

const okResponse = (data: unknown) =>
  ({ ok: true, json: async () => ({ success: true, message: "Success", data }) }) as unknown as Response;

describe("getLandingData", () => {
  afterEach(() => {
    delete process.env.LANDING_DATA_SOURCE;
  });

  it("devuelve el fixture cuando LANDING_DATA_SOURCE=fixture", async () => {
    process.env.LANDING_DATA_SOURCE = "fixture";
    const fetcher = vi.fn();
    expect(await getLandingData(fetcher as unknown as typeof fetch)).toBe(LANDING_FIXTURE);
    expect(fetcher).not.toHaveBeenCalled();
  });

  it("consulta /api/v1/public/landing con revalidate 300", async () => {
    const fetcher = vi.fn(async () => okResponse(LANDING_FIXTURE));
    const data = await getLandingData(fetcher as unknown as typeof fetch);
    expect(data?.plans).toHaveLength(4);
    const [url, init] = fetcher.mock.calls[0] as unknown as [string, { next?: { revalidate?: number } }];
    expect(url).toMatch(/\/api\/v1\/public\/landing$/);
    expect(init.next?.revalidate).toBe(300);
  });

  it("devuelve null si la respuesta no es ok, no tiene la forma esperada o falla", async () => {
    expect(await getLandingData((async () => ({ ok: false })) as unknown as typeof fetch)).toBeNull();
    expect(await getLandingData((async () => okResponse({ plans: "no" })) as unknown as typeof fetch)).toBeNull();
    expect(await getLandingData((async () => { throw new Error("red"); }) as unknown as typeof fetch)).toBeNull();
  });
});

describe("isLandingData", () => {
  it("valida la forma mínima", () => {
    expect(isLandingData(LANDING_FIXTURE)).toBe(true);
    expect(isLandingData({ plans: [], pricing_content: { title: "x" } })).toBe(false);
    expect(isLandingData(null)).toBe(false);
  });
});
```

`frontend/tests/unit/jsonld.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import { FAQS, FEATURE_LIST_FOR_SEO, SITE_DESCRIPTION } from "@/content/landing";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";
import { buildJsonLd } from "@/lib/landing/jsonld";

const input = {
  baseUrl: "https://facturon.ec",
  plans: LANDING_FIXTURE.plans,
  faqs: FAQS,
  featureList: FEATURE_LIST_FOR_SEO,
  contactEmail: "info@amephia.com",
  whatsappDigits: "13347324056",
  description: SITE_DESCRIPTION,
};

type Node = Record<string, unknown> & { "@type": string };

describe("buildJsonLd", () => {
  const graph = buildJsonLd(input)["@graph"] as Node[];
  const byType = (t: string) => graph.find((n) => n["@type"] === t) as Node;

  it("tiene Organization, SoftwareApplication y FAQPage", () => {
    expect(graph.map((n) => n["@type"])).toEqual(["Organization", "SoftwareApplication", "FAQPage"]);
  });

  it("la Organization lleva contacto y el software la referencia", () => {
    const org = byType("Organization");
    expect(org.name).toBe("AmePhia Systems Inc.");
    const contact = (org.contactPoint as Array<Record<string, unknown>>)[0];
    expect(contact.email).toBe("info@amephia.com");
    expect(contact.telephone).toBe("+13347324056");
    expect((byType("SoftwareApplication").publisher as { "@id": string })["@id"]).toBe("https://facturon.ec/#organization");
  });

  it("las ofertas son los planes reales en USD, sin aggregateRating", () => {
    const app = byType("SoftwareApplication");
    const aggregate = (app.offers as Array<Record<string, unknown>>)[0];
    expect(aggregate["@type"]).toBe("AggregateOffer");
    expect(aggregate.lowPrice).toBe("2.99");
    expect(aggregate.highPrice).toBe("49.99");
    expect((aggregate.offers as unknown[]).length).toBe(4);
    expect(app.aggregateRating).toBeUndefined();
    expect(app.url).toBe("https://facturon.ec/");
  });

  it("sin planes no incluye offers", () => {
    const app = (buildJsonLd({ ...input, plans: [] })["@graph"] as Node[])[1];
    expect(app.offers).toBeUndefined();
  });

  it("la FAQPage tiene las 13 preguntas", () => {
    expect((byType("FAQPage").mainEntity as unknown[]).length).toBe(13);
  });
});
```

Run: `pnpm test`
Expected: FAIL.

- [ ] **Step 2: `fixture.ts`**

```ts
import type { LandingData } from "./pricing";

/** Datos de ejemplo (espejo de producción) para tests e2e y desarrollo sin backend. */
export const LANDING_FIXTURE: LandingData = {
  plans: [
    {
      id: 1,
      name: "Emprendedor",
      slug: "emprendedor",
      description: "Para profesionales y negocios que empiezan.",
      price_monthly: 2.99,
      price_yearly: 29.9,
      currency: "USD",
      is_featured: false,
      yearly_savings_percent: 17,
      features_list: ["20 documentos/mes", "3 usuarios", "1 empresa (RUC)", "2 puntos de emisión", "Proformas", "ATS", "Acceso para contador", "Soporte por email (48h)"],
    },
    {
      id: 2,
      name: "Negocio",
      slug: "negocio",
      description: "Ideal para PyMEs con operaciones frecuentes y necesidad de control de inventario.",
      price_monthly: 7.99,
      price_yearly: 79.9,
      currency: "USD",
      is_featured: true,
      yearly_savings_percent: 17,
      features_list: ["50 documentos/mes", "10 usuarios", "1 empresa (RUC)", "5 puntos de emisión", "Proformas", "ATS", "Acceso para contador", "API REST", "Inventario", "Punto de venta", "Facturación recurrente", "Reportes avanzados", "Impresora térmica", "Portal de clientes", "Soporte por email (24h)"],
    },
    {
      id: 3,
      name: "Profesional",
      slug: "profesional",
      description: "Para empresas con varios RUC y más volumen.",
      price_monthly: 14.99,
      price_yearly: 149.9,
      currency: "USD",
      is_featured: false,
      yearly_savings_percent: 17,
      features_list: ["Documentos ilimitados", "Usuarios ilimitados", "Hasta 3 empresas (RUC)", "Puntos de emisión ilimitados", "Proformas", "ATS", "Acceso para contador", "API REST", "Inventario", "Punto de venta", "Facturación recurrente", "Reportes avanzados", "Impresora térmica", "Portal de clientes", "Multi-moneda", "RIDE personalizado", "Categorización con IA"],
    },
    {
      id: 4,
      name: "Enterprise",
      slug: "enterprise",
      description: "Para grupos empresariales con requisitos especiales.",
      price_monthly: 49.99,
      price_yearly: 499,
      currency: "USD",
      is_featured: false,
      yearly_savings_percent: 17,
      features_list: ["Documentos ilimitados", "Usuarios ilimitados", "Empresas (RUC) ilimitadas", "Puntos de emisión ilimitados", "Todo lo del plan Profesional", "Emisión prioritaria al SRI", "Operaciones masivas", "Roles personalizados", "Gerente de cuenta dedicado", "SLA"],
    },
  ],
  pricing_content: {
    eyebrow: "Planes",
    title: "Precios transparentes, sin sorpresas",
    subtitle: "Sin comisiones por documento. Escoge el plan que se ajuste a tu negocio.",
    badge_enabled: true,
    badge_text: "Administra varias empresas (RUCs) desde una sola cuenta",
    footer_note: "Todos los planes incluyen soporte por email. Pago seguro por transferencia bancaria.",
  },
};
```

- [ ] **Step 3: `data.ts`**

```ts
import type { ApiSuccess } from "@/lib/api/types";
import { LANDING_FIXTURE } from "./fixture";
import type { LandingData, LandingPlan, PricingContent } from "./pricing";

export const LANDING_REVALIDATE_SECONDS = 300;

const API_BASE = (process.env.LARAVEL_API_URL ?? "http://localhost:8000").replace(/\/+$/, "");

const CONTENT_KEYS: Array<keyof PricingContent> = ["eyebrow", "title", "subtitle", "badge_enabled", "badge_text", "footer_note"];

function isPlan(value: unknown): value is LandingPlan {
  if (!value || typeof value !== "object") return false;
  const p = value as Record<string, unknown>;
  return (
    typeof p.id === "number" &&
    typeof p.name === "string" &&
    typeof p.slug === "string" &&
    typeof p.price_monthly === "number" &&
    typeof p.price_yearly === "number" &&
    Array.isArray(p.features_list)
  );
}

export function isLandingData(value: unknown): value is LandingData {
  if (!value || typeof value !== "object") return false;
  const v = value as Record<string, unknown>;
  if (!Array.isArray(v.plans) || !v.plans.every(isPlan)) return false;
  const content = v.pricing_content as Record<string, unknown> | undefined;
  if (!content || typeof content !== "object") return false;
  return CONTENT_KEYS.every((key) => key in content);
}

/**
 * Planes + textos del CMS para la landing. Con LANDING_DATA_SOURCE=fixture
 * devuelve datos de ejemplo (tests e2e, desarrollo sin backend). Ante cualquier
 * error devuelve null y la landing se renderiza sin la sección de precios.
 */
export async function getLandingData(fetcher: typeof fetch = fetch): Promise<LandingData | null> {
  if (process.env.LANDING_DATA_SOURCE === "fixture") return LANDING_FIXTURE;

  try {
    const res = await fetcher(`${API_BASE}/api/v1/public/landing`, {
      headers: { Accept: "application/json" },
      next: { revalidate: LANDING_REVALIDATE_SECONDS },
    });
    if (!res.ok) return null;
    const json = (await res.json()) as ApiSuccess<unknown>;
    return isLandingData(json.data) ? json.data : null;
  } catch {
    return null;
  }
}
```

- [ ] **Step 4: `jsonld.ts`**

```ts
import type { FaqItem } from "@/content/landing";
import type { LandingPlan } from "./pricing";

export type JsonLdInput = {
  baseUrl: string;
  plans: LandingPlan[];
  faqs: FaqItem[];
  featureList: string[];
  contactEmail: string;
  whatsappDigits: string;
  description: string;
};

/** Grafo schema.org: Organization + SoftwareApplication (ofertas reales) + FAQPage. */
export function buildJsonLd(input: JsonLdInput) {
  const orgId = `${input.baseUrl}/#organization`;

  const organization = {
    "@type": "Organization",
    "@id": orgId,
    name: "AmePhia Systems Inc.",
    url: "https://amephia.com",
    logo: `${input.baseUrl}/marketing/icon-facturon-512.png`,
    contactPoint: [
      {
        "@type": "ContactPoint",
        contactType: "sales",
        email: input.contactEmail,
        telephone: `+${input.whatsappDigits}`,
        areaServed: "EC",
        availableLanguage: ["es"],
      },
    ],
  };

  const prices = input.plans.map((p) => p.price_monthly);
  const offers =
    input.plans.length === 0
      ? {}
      : {
          offers: [
            {
              "@type": "AggregateOffer",
              priceCurrency: "USD",
              lowPrice: Math.min(...prices).toFixed(2),
              highPrice: Math.max(...prices).toFixed(2),
              offerCount: input.plans.length,
              offers: input.plans.map((p) => ({
                "@type": "Offer",
                name: p.name,
                price: p.price_monthly.toFixed(2),
                priceCurrency: p.currency,
                url: `${input.baseUrl}/register?plan=${p.slug}`,
                availability: "https://schema.org/InStock",
                category: "subscription",
              })),
            },
          ],
        };

  const software = {
    "@type": "SoftwareApplication",
    "@id": `${input.baseUrl}/#software`,
    name: "Facturón",
    alternateName: "Facturón EC",
    url: `${input.baseUrl}/`,
    description: input.description,
    applicationCategory: "BusinessApplication",
    operatingSystem: "Web, Android, iOS",
    inLanguage: "es-EC",
    countriesSupported: "EC",
    featureList: input.featureList,
    publisher: { "@id": orgId },
    ...offers,
  };

  const faq = {
    "@type": "FAQPage",
    "@id": `${input.baseUrl}/#faq`,
    mainEntity: input.faqs.map((f) => ({
      "@type": "Question",
      name: f.q,
      acceptedAnswer: { "@type": "Answer", text: f.a },
    })),
  };

  return { "@context": "https://schema.org", "@graph": [organization, software, faq] };
}
```

- [ ] **Step 5: Verificar y commit**

Run: `pnpm test && pnpm typecheck`
Expected: PASS.

```bash
git add frontend/src/lib/landing/fixture.ts frontend/src/lib/landing/data.ts frontend/src/lib/landing/jsonld.ts frontend/tests/unit/data.test.ts frontend/tests/unit/jsonld.test.ts
git commit -m "feat(landing): carga de datos con fixture y constructor de JSON-LD"
```

---
### Task 7: Tokens de marca y shell de la landing (layout, nav, footer, WhatsApp, badges, reveal, marcos)

**Files:**
- Modify: `frontend/src/app/globals.css` (tokens y utilidad al final del archivo)
- Create: `frontend/src/app/(marketing)/layout.tsx`, `frontend/src/components/marketing/logo.tsx`, `nav.tsx`, `nav-client.tsx`, `footer.tsx`, `whatsapp-button.tsx`, `store-badges.tsx`, `reveal.tsx`, `browser-frame.tsx`, `phone-frame.tsx`, `icons.ts`
- Test: `frontend/tests/unit/store-badges.test.tsx`, `frontend/tests/unit/reveal.test.tsx`

**Interfaces:**
- Clases Tailwind nuevas: `bg-navy`, `bg-navy-2`, `bg-navy-3`, `text-navy`, `bg-brand`, `text-brand`, `bg-brand-hover`, `font-display`, `bg-flag-gradient`.
- `FacturonLogo({ tone?: "dark" | "light"; className?: string })`, `FacturonGlyph({ className })`.
- `MarketingNav()` (server, async) → `NavClient({ isAuthenticated: boolean })`.
- `SiteFooter()`, `WhatsAppButton()`, `StoreBadges({ playUrl?, appStoreUrl?, className? })`, `Reveal({ children, className?, delay? })`, `BrowserFrame({ url, children, className? })`, `PhoneFrame({ children, className? })`.
- `ICONS: Record<IconName, LucideIcon>` en `icons.ts`.

- [ ] **Step 1: Tests que fallan**

`frontend/tests/unit/store-badges.test.tsx`:

```tsx
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { StoreBadges } from "@/components/marketing/store-badges";

describe("StoreBadges", () => {
  it("enlaza a las tiendas cuando hay URL", () => {
    render(<StoreBadges playUrl="https://play.google.com/store/apps/details?id=x" appStoreUrl="https://apps.apple.com/app/id1" />);
    const links = screen.getAllByRole("link");
    expect(links).toHaveLength(2);
    expect(links[0]).toHaveAttribute("href", expect.stringContaining("play.google.com"));
    expect(links[1]).toHaveAttribute("href", expect.stringContaining("apps.apple.com"));
    expect(screen.queryByText("Próximamente")).toBeNull();
  });

  it("muestra Próximamente sin enlaces cuando faltan las URL", () => {
    render(<StoreBadges />);
    expect(screen.queryAllByRole("link")).toHaveLength(0);
    expect(screen.getAllByText("Próximamente")).toHaveLength(2);
    expect(screen.getByText("Google Play")).toBeInTheDocument();
    expect(screen.getByText("App Store")).toBeInTheDocument();
  });
});
```

`frontend/tests/unit/reveal.test.tsx`:

```tsx
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Reveal } from "@/components/marketing/reveal";

describe("Reveal", () => {
  it("renderiza a sus hijos en el DOM (indexables aunque animen)", () => {
    render(<Reveal><p>Contenido visible</p></Reveal>);
    expect(screen.getByText("Contenido visible")).toBeInTheDocument();
  });
});
```

Run: `pnpm test`
Expected: FAIL.

- [ ] **Step 2: Tokens en `globals.css`**

Añadir al final de `frontend/src/app/globals.css`:

```css
/* ------------------------------------------------------------------------
   Landing (marketing): marca Facturón. Colores explícitos, sin dark mode.
   ------------------------------------------------------------------------ */
@theme inline {
  --color-navy: #0b1220;
  --color-navy-2: #101b31;
  --color-navy-3: #162037;
  --color-brand: #2b54e4;
  --color-brand-hover: #2446c4;
  --font-display: var(--font-bricolage), var(--font-geist-sans), ui-sans-serif, system-ui, sans-serif;
}

@utility bg-flag-gradient {
  background-image: linear-gradient(90deg, #ffce00 0%, #0653c6 50%, #ef3340 100%);
}
```

- [ ] **Step 3: `icons.ts`, `logo.tsx`, marcos y `reveal.tsx`**

`frontend/src/components/marketing/icons.ts`:

```ts
import {
  BookOpen,
  Building2,
  Code2,
  FileCheck2,
  FileMinus,
  FilePlus2,
  FileText,
  Mail,
  Package,
  Receipt,
  Repeat,
  ShieldCheck,
  Smartphone,
  Sparkles,
  Store,
  Truck,
  Users,
  Zap,
  type LucideIcon,
} from "lucide-react";
import type { IconName } from "@/content/landing";

export const ICONS: Record<IconName, LucideIcon> = {
  ShieldCheck,
  Zap,
  Mail,
  Store,
  Package,
  BookOpen,
  Users,
  Building2,
  Code2,
  Smartphone,
  Repeat,
  Sparkles,
  FileText,
  FileMinus,
  FilePlus2,
  Truck,
  Receipt,
  FileCheck2,
};
```

`frontend/src/components/marketing/logo.tsx`:

```tsx
import Link from "next/link";
import { cn } from "@/lib/utils";

/** Glifo del icono de la app: cuadrado con borde bandera y "F" en blanco. */
export function FacturonGlyph({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 1024 1024" className={className} aria-hidden="true" focusable="false">
      <defs>
        <linearGradient id="facturon-flag" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#FFCE00" />
          <stop offset="48%" stopColor="#0653C6" />
          <stop offset="100%" stopColor="#EF3340" />
        </linearGradient>
      </defs>
      <rect width="1024" height="1024" rx="230" fill="url(#facturon-flag)" />
      <rect x="110" y="110" width="804" height="804" rx="190" fill="#0B1424" />
      <rect x="400" y="300" width="100" height="424" rx="16" fill="#FFFFFF" />
      <rect x="400" y="300" width="260" height="100" rx="16" fill="#FFFFFF" />
      <rect x="400" y="440" width="200" height="96" rx="16" fill="#FFFFFF" />
    </svg>
  );
}

export function FacturonLogo({ tone = "dark", className }: { tone?: "dark" | "light"; className?: string }) {
  return (
    <Link href="/" className={cn("inline-flex items-center gap-2.5", className)} aria-label="Facturón, ir al inicio">
      <FacturonGlyph className="size-8 shrink-0" />
      <span className={cn("font-display text-lg font-bold tracking-tight", tone === "light" ? "text-white" : "text-navy")}>
        Facturón
      </span>
    </Link>
  );
}
```

`frontend/src/components/marketing/browser-frame.tsx`:

```tsx
import { Lock } from "lucide-react";
import { cn } from "@/lib/utils";

export function BrowserFrame({ url, children, className }: { url: string; children: React.ReactNode; className?: string }) {
  return (
    <div className={cn("overflow-hidden rounded-2xl border border-white/10 bg-navy-3 shadow-2xl shadow-black/40", className)}>
      <div className="flex items-center gap-3 border-b border-white/10 bg-black/20 px-4 py-2.5">
        <div className="flex gap-1.5" aria-hidden="true">
          <span className="size-2.5 rounded-full bg-white/15" />
          <span className="size-2.5 rounded-full bg-white/15" />
          <span className="size-2.5 rounded-full bg-white/15" />
        </div>
        <div className="mx-auto flex items-center gap-1.5 rounded-md bg-white/5 px-3 py-1 font-mono text-[11px] text-slate-400">
          <Lock className="size-3 text-emerald-400" aria-hidden="true" />
          {url}
        </div>
      </div>
      {children}
    </div>
  );
}
```

`frontend/src/components/marketing/phone-frame.tsx`:

```tsx
import { cn } from "@/lib/utils";

export function PhoneFrame({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <div className={cn("mx-auto w-[260px] rounded-[2.4rem] border-[6px] border-slate-900 bg-slate-900 p-1.5 shadow-2xl shadow-slate-900/30", className)}>
      <div className="overflow-hidden rounded-[1.9rem] bg-white">{children}</div>
    </div>
  );
}
```

`frontend/src/components/marketing/reveal.tsx`:

```tsx
"use client";

import { motion, useReducedMotion } from "motion/react";

/** Aparece con un fade + 12 px al entrar en viewport, una sola vez. */
export function Reveal({ children, className, delay = 0 }: { children: React.ReactNode; className?: string; delay?: number }) {
  const reduce = useReducedMotion();
  if (reduce) return <div className={className}>{children}</div>;
  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, y: 12 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-80px" }}
      transition={{ duration: 0.4, delay, ease: "easeOut" }}
    >
      {children}
    </motion.div>
  );
}
```

- [ ] **Step 4: `store-badges.tsx` y `whatsapp-button.tsx`**

`frontend/src/components/marketing/store-badges.tsx`:

```tsx
import { cn } from "@/lib/utils";

function PlayGlyph() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
      <path fill="currentColor" d="M3.6 2.3 13 12l-9.4 9.7c-.4-.2-.6-.6-.6-1.1V3.4c0-.5.2-.9.6-1.1Zm11.8 7.3L6.1 4.2l8.6 8.6 3.3-3.2Zm3.5 1.2 2.6 1.5c.7.4.7 1 0 1.4l-2.6 1.5L16 12l2.9-1.2ZM6.1 19.8l9.3-5.4-3.3-3.2-6 8.6Z" />
    </svg>
  );
}

function AppleGlyph() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
      <path fill="currentColor" d="M16.4 12.6c0-2.4 2-3.6 2.1-3.7-1.1-1.7-2.9-1.9-3.5-1.9-1.5-.2-2.9.9-3.7.9-.8 0-1.9-.9-3.2-.8-1.6 0-3.1 1-4 2.4-1.7 3-.4 7.3 1.2 9.7.8 1.2 1.8 2.5 3 2.4 1.2 0 1.7-.8 3.2-.8s1.9.8 3.2.8c1.3 0 2.2-1.2 3-2.4.9-1.4 1.3-2.7 1.3-2.8-.1 0-2.6-1-2.6-3.8ZM14 5.5c.7-.8 1.1-2 1-3.1-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4Z" />
    </svg>
  );
}

function Badge({ href, top, name, glyph }: { href: string; top: string; name: string; glyph: React.ReactNode }) {
  const body = (
    <>
      <span className="text-slate-300">{glyph}</span>
      <span className="flex flex-col leading-none">
        <span className="text-[10px] uppercase tracking-wide text-slate-400">{href ? top : "Próximamente"}</span>
        <span className="mt-1 text-sm font-semibold text-white">{name}</span>
      </span>
    </>
  );
  const base = "inline-flex h-12 items-center gap-2.5 rounded-xl border border-white/15 bg-black/40 px-3.5";
  if (!href) {
    return (
      <span className={cn(base, "opacity-70")} aria-disabled="true">
        {body}
      </span>
    );
  }
  return (
    <a href={href} target="_blank" rel="noopener noreferrer" className={cn(base, "transition-colors hover:border-white/30 hover:bg-black/60")}>
      {body}
    </a>
  );
}

export function StoreBadges({ playUrl = "", appStoreUrl = "", className }: { playUrl?: string; appStoreUrl?: string; className?: string }) {
  return (
    <div className={cn("flex flex-wrap gap-3", className)}>
      <Badge href={playUrl} top="Disponible en" name="Google Play" glyph={<PlayGlyph />} />
      <Badge href={appStoreUrl} top="Descárgala en" name="App Store" glyph={<AppleGlyph />} />
    </div>
  );
}
```

`frontend/src/components/marketing/whatsapp-button.tsx`:

```tsx
import { whatsappUrl } from "@/lib/landing/config";

export function WhatsAppGlyph({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
      <path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
    </svg>
  );
}

export function WhatsAppButton() {
  return (
    <a
      href={whatsappUrl()}
      target="_blank"
      rel="noopener noreferrer"
      aria-label="Escribir por WhatsApp"
      className="fixed bottom-5 right-5 z-40 inline-flex size-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl shadow-black/25 transition-transform hover:scale-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
    >
      <WhatsAppGlyph className="size-7" />
    </a>
  );
}
```

- [ ] **Step 5: Nav (server + client)**

`frontend/src/components/marketing/nav.tsx`:

```tsx
import { getSession } from "@/lib/auth/session";
import { NavClient } from "./nav-client";

/** Lee la cookie de sesión en el servidor y delega la UI al cliente. */
export async function MarketingNav() {
  const session = await getSession();
  return <NavClient isAuthenticated={session !== null} />;
}
```

`frontend/src/components/marketing/nav-client.tsx`:

```tsx
"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Menu, X } from "lucide-react";
import { NAV_LINKS } from "@/content/landing";
import { cn } from "@/lib/utils";
import { FacturonLogo } from "./logo";

const CTA = "inline-flex h-10 items-center rounded-full bg-brand px-5 text-sm font-semibold text-white shadow-lg shadow-brand/30 transition-colors hover:bg-brand-hover";

export function NavClient({ isAuthenticated }: { isAuthenticated: boolean }) {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <header
      className={cn(
        "fixed inset-x-0 top-0 z-50 transition-colors duration-300",
        scrolled || open ? "border-b border-white/10 bg-navy/85 backdrop-blur-md" : "bg-transparent",
      )}
    >
      <nav className="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8" aria-label="Principal">
        <FacturonLogo tone="light" />

        <ul className="hidden items-center gap-1 lg:flex">
          {NAV_LINKS.map((link) => (
            <li key={link.href}>
              <a href={link.href} className="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-white/5 hover:text-white">
                {link.label}
              </a>
            </li>
          ))}
        </ul>

        <div className="flex items-center gap-2">
          {isAuthenticated ? (
            <Link href="/dashboard" className={CTA}>Ir a mi panel</Link>
          ) : (
            <>
              <Link href="/login" className="hidden h-10 items-center px-4 text-sm font-medium text-slate-300 transition-colors hover:text-white sm:inline-flex">
                Ingresar
              </Link>
              <Link href="/register" className={CTA}>Crear cuenta</Link>
            </>
          )}
          <button
            type="button"
            onClick={() => setOpen((v) => !v)}
            className="ml-1 inline-flex size-10 items-center justify-center rounded-lg text-slate-300 hover:bg-white/5 hover:text-white lg:hidden"
            aria-expanded={open}
            aria-controls="menu-movil"
            aria-label={open ? "Cerrar menú" : "Abrir menú"}
          >
            {open ? <X className="size-5" /> : <Menu className="size-5" />}
          </button>
        </div>
      </nav>

      {open && (
        <div id="menu-movil" className="border-t border-white/10 bg-navy px-5 pb-6 pt-3 lg:hidden">
          <ul className="flex flex-col gap-1">
            {NAV_LINKS.map((link) => (
              <li key={link.href}>
                <a href={link.href} onClick={() => setOpen(false)} className="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5">
                  {link.label}
                </a>
              </li>
            ))}
            {!isAuthenticated && (
              <li>
                <Link href="/login" className="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5">
                  Ingresar
                </Link>
              </li>
            )}
          </ul>
        </div>
      )}
    </header>
  );
}
```

- [ ] **Step 6: Footer**

`frontend/src/components/marketing/footer.tsx`:

```tsx
import Link from "next/link";
import { CONTACT_EMAIL, STORE_APPSTORE_URL, STORE_PLAY_URL, formatWhatsapp, whatsappUrl } from "@/lib/landing/config";
import { FacturonLogo } from "./logo";

type FooterLink = { label: string; href: string; disabled?: boolean };

const NEXT_ROUTES = new Set(["/login", "/register", "/dashboard"]);

const COLUMNS: Array<{ title: string; links: FooterLink[] }> = [
  {
    title: "Producto",
    links: [
      { label: "Funcionalidades", href: "#funcionalidades" },
      { label: "Precios", href: "#precios" },
      { label: "Preguntas frecuentes", href: "#faq" },
      { label: "App para Android", href: STORE_PLAY_URL, disabled: STORE_PLAY_URL === "" },
      { label: "App para iOS", href: STORE_APPSTORE_URL, disabled: STORE_APPSTORE_URL === "" },
    ],
  },
  {
    title: "Cuenta",
    links: [
      { label: "Ingresar", href: "/login" },
      { label: "Crear cuenta", href: "/register" },
    ],
  },
  {
    title: "Legal",
    links: [
      { label: "Términos y condiciones", href: "/terms" },
      { label: "Política de privacidad", href: "/privacy" },
      { label: "Eliminación de cuenta", href: "/delete-account" },
    ],
  },
  {
    title: "Contacto",
    links: [
      { label: `WhatsApp ${formatWhatsapp()}`, href: whatsappUrl() },
      { label: CONTACT_EMAIL, href: `mailto:${CONTACT_EMAIL}` },
      { label: "AmePhia Systems", href: "https://amephia.com" },
    ],
  },
];

function FooterAnchor({ link }: { link: FooterLink }) {
  const cls = "text-sm text-slate-500 transition-colors hover:text-navy";
  if (link.disabled) {
    return (
      <span className="text-sm text-slate-400">
        {link.label} <span className="text-xs">(próximamente)</span>
      </span>
    );
  }
  if (NEXT_ROUTES.has(link.href)) {
    return <Link href={link.href} className={cls}>{link.label}</Link>;
  }
  const external = link.href.startsWith("http") || link.href.startsWith("mailto:");
  return (
    <a href={link.href} className={cls} {...(external ? { target: "_blank", rel: "noopener noreferrer" } : {})}>
      {link.label}
    </a>
  );
}

export function SiteFooter() {
  return (
    <footer className="border-t border-slate-200 bg-white">
      <div className="mx-auto max-w-6xl px-5 py-14 sm:px-8">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
          <div className="lg:col-span-4">
            <FacturonLogo />
            <p className="mt-3 max-w-xs text-sm leading-relaxed text-slate-500">
              Facturación electrónica del Ecuador. Comprobantes autorizados por el SRI, firmados con tu certificado.
            </p>
          </div>
          {COLUMNS.map((col) => (
            <div key={col.title} className="lg:col-span-2">
              <h2 className="text-xs font-semibold uppercase tracking-wider text-navy">{col.title}</h2>
              <ul className="mt-4 space-y-2.5">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <FooterAnchor link={link} />
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="mt-12 flex flex-col gap-4 border-t border-slate-200 pt-8 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
          <p>© {new Date().getFullYear()} AmePhia Systems Inc. · Hecho en Ecuador 🇪🇨</p>
          <p className="max-w-xl sm:text-right">
            Facturón es un producto de AmePhia Systems Inc. No está afiliado al SRI; la autorización de cada comprobante la emite el SRI.
          </p>
        </div>
      </div>
    </footer>
  );
}
```

- [ ] **Step 7: Layout del grupo `(marketing)`**

`frontend/src/app/(marketing)/layout.tsx`:

```tsx
import type { Metadata } from "next";
import { Bricolage_Grotesque } from "next/font/google";
import { SITE_DESCRIPTION } from "@/content/landing";
import { APP_URL } from "@/lib/landing/config";
import { WhatsAppButton } from "@/components/marketing/whatsapp-button";

const bricolage = Bricolage_Grotesque({
  subsets: ["latin"],
  weight: ["700", "800"],
  variable: "--font-bricolage",
  display: "swap",
});

const TITLE = "Facturón — Facturación electrónica del Ecuador autorizada por el SRI";
const OG_TITLE = "Facturón — Facturación electrónica del Ecuador";

export const metadata: Metadata = {
  metadataBase: new URL(APP_URL),
  title: { absolute: TITLE },
  description: SITE_DESCRIPTION,
  alternates: { canonical: "/", languages: { "es-EC": "/" } },
  openGraph: {
    type: "website",
    locale: "es_EC",
    url: "/",
    siteName: "Facturón",
    title: OG_TITLE,
    description: SITE_DESCRIPTION,
    images: [{ url: "/marketing/og.png", width: 1200, height: 630, alt: "Facturón — facturación electrónica del Ecuador" }],
  },
  twitter: { card: "summary_large_image", title: OG_TITLE, description: SITE_DESCRIPTION, images: ["/marketing/og.png"] },
  robots: { index: true, follow: true },
};

export default function MarketingLayout({ children }: { children: React.ReactNode }) {
  return (
    <div id="landing" className={`${bricolage.variable} min-h-screen bg-white text-slate-900 antialiased`}>
      <a
        href="#contenido"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-navy focus:shadow-lg"
      >
        Ir al contenido
      </a>
      {children}
      <WhatsAppButton />
    </div>
  );
}
```

- [ ] **Step 8: Verificar y commit**

Run: `pnpm test && pnpm typecheck && pnpm lint`
Expected: PASS.

```bash
git add frontend/src/app/globals.css "frontend/src/app/(marketing)/layout.tsx" frontend/src/components/marketing frontend/tests/unit/store-badges.test.tsx frontend/tests/unit/reveal.test.tsx
git commit -m "feat(landing): tokens de marca y shell (layout, nav, footer, WhatsApp, badges, marcos)"
```

---

### Task 8: Hero con demo "Emisión en vivo" y franja de confianza

**Files:**
- Create: `frontend/src/components/marketing/hero.tsx`, `live-demo.tsx`, `trust-strip.tsx`
- Test: `frontend/tests/unit/live-demo.test.tsx`

**Interfaces:**
- `Hero({ cheapest: LandingPlan | null })`, `LiveDemo()`, `TrustStrip()`.
- La demo expone `data-testid="demo-stamp"` en el sello AUTORIZADO (lo usa el e2e).

- [ ] **Step 1: Test que falla**

`frontend/tests/unit/live-demo.test.tsx`:

```tsx
import { act, render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

// En jsdom no hay IntersectionObserver real: forzamos "en pantalla".
vi.mock("motion/react", async (importOriginal) => {
  const actual = await importOriginal<typeof import("motion/react")>();
  return { ...actual, useInView: () => true };
});

import { LiveDemo } from "@/components/marketing/live-demo";

describe("LiveDemo", () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it("arranca en borrador y llega a AUTORIZADO con clave de acceso", () => {
    render(<LiveDemo />);
    expect(screen.getByText("001-001-000000123")).toBeInTheDocument();
    expect(screen.getByText("Borrador")).toBeInTheDocument();
    expect(screen.queryByTestId("demo-stamp")).toBeNull();

    act(() => vi.advanceTimersByTime(2500 + 1800 + 2200 + 50));
    expect(screen.getByTestId("demo-stamp")).toHaveTextContent("AUTORIZADO");

    act(() => vi.advanceTimersByTime(49 * 45 + 100));
    expect(screen.getByTestId("demo-clave").textContent?.replace(/\D/g, "")).toHaveLength(49);
  });

  it("describe el flujo para lectores de pantalla", () => {
    render(<LiveDemo />);
    expect(screen.getByText(/se firma con tu certificado/i)).toBeInTheDocument();
  });
});
```

Run: `pnpm test -- live-demo`
Expected: FAIL.

- [ ] **Step 2: `trust-strip.tsx`**

```tsx
import { Check } from "lucide-react";
import { TRUST_CHIPS } from "@/content/landing";

export function TrustStrip() {
  return (
    <ul className="mt-14 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-white/10 pt-6 text-xs text-slate-400" aria-label="Cumplimiento técnico">
      {TRUST_CHIPS.map((chip) => (
        <li key={chip} className="inline-flex items-center gap-1.5">
          <Check className="size-3.5 shrink-0 text-emerald-400" aria-hidden="true" />
          {chip}
        </li>
      ))}
    </ul>
  );
}
```

- [ ] **Step 3: `live-demo.tsx`**

```tsx
"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { AnimatePresence, motion, useInView, useReducedMotion } from "motion/react";
import { CheckCircle2, FileSignature, Mail, Send, ShieldCheck, type LucideIcon } from "lucide-react";
import { claveAcceso } from "@/lib/landing/clave-acceso";
import { DEMO_INVOICE, isAtLeast, stepIndex, type DemoStep } from "@/lib/landing/demo-machine";
import { formatPrice } from "@/lib/landing/pricing";
import { useDemoSequence } from "@/lib/landing/use-demo-sequence";
import { cn } from "@/lib/utils";
import { BrowserFrame } from "./browser-frame";

const DEMO_HOST = (process.env.NEXT_PUBLIC_APP_URL ?? "https://facturon.ec").replace(/^https?:\/\//, "");

/** Efecto máquina de escribir: prefijo visible de `text` mientras `active`. */
function useTypedText(text: string, active: boolean, msPerChar = 45): string {
  const [count, setCount] = useState(0);
  useEffect(() => {
    if (!active) {
      setCount(0);
      return;
    }
    if (count >= text.length) return;
    const id = window.setTimeout(() => setCount((c) => c + 1), msPerChar);
    return () => window.clearTimeout(id);
  }, [active, count, text.length, msPerChar]);
  return active ? text.slice(0, count) : "";
}

/** Cuenta los ciclos para re-animar las líneas de la factura en cada vuelta. */
function useCycle(step: DemoStep): number {
  const [cycle, setCycle] = useState(0);
  useEffect(() => {
    if (step === "draft") setCycle((c) => c + 1);
  }, [step]);
  return cycle;
}

const STATUS: Array<{ step: DemoStep; label: string; icon: LucideIcon; process: boolean }> = [
  { step: "signing", label: "Firmada con tu certificado · XAdES-BES", icon: FileSignature, process: true },
  { step: "sending", label: "Recibida por el SRI", icon: Send, process: true },
  { step: "authorized", label: "Autorizada por el SRI", icon: ShieldCheck, process: false },
  { step: "delivered", label: "RIDE (PDF) + XML enviados al cliente", icon: Mail, process: false },
];

export function LiveDemo() {
  const ref = useRef<HTMLDivElement>(null);
  const inView = useInView(ref, { amount: 0.3 });
  const reduce = useReducedMotion() ?? false;
  const step = useDemoSequence(inView && !reduce, reduce ? "authorized" : "draft");
  const cycle = useCycle(step);
  const clave = useMemo(
    () => claveAcceso({ date: new Date(), ruc: DEMO_INVOICE.emitterRuc, sequential: "123" }),
    [],
  );
  const authorized = isAtLeast(step, "authorized");
  const typed = useTypedText(clave, authorized && !reduce);
  const shownClave = reduce ? clave : typed;
  const typing = authorized && !reduce && shownClave.length < clave.length;

  return (
    <div ref={ref} className="relative" aria-hidden="true">
      <p className="sr-only">
        Demostración: una factura se crea, se firma con tu certificado, se envía al SRI, queda autorizada con su clave de acceso y se envía al cliente en PDF y XML.
      </p>
      <div aria-hidden="true" className="absolute -inset-6 rounded-[2rem] bg-brand/20 blur-3xl" />
      <BrowserFrame url={`${DEMO_HOST}/documents/new`} className="relative">
        <div className="grid gap-4 p-4 sm:grid-cols-5 sm:p-5">
          {/* Factura */}
          <div className="rounded-xl border border-white/10 bg-navy-2 p-4 sm:col-span-3">
            <div className="flex items-start justify-between gap-3">
              <div>
                <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Factura</p>
                <p className="font-mono text-sm font-semibold text-white">{DEMO_INVOICE.number}</p>
              </div>
              <AnimatePresence mode="wait" initial={false}>
                {authorized ? (
                  <motion.span
                    key="stamp"
                    data-testid="demo-stamp"
                    initial={reduce ? false : { scale: 0.6, opacity: 0, rotate: -12 }}
                    animate={{ scale: 1, opacity: 1, rotate: -6 }}
                    exit={{ opacity: 0 }}
                    className="rounded-md border-2 border-emerald-400 px-2 py-0.5 font-display text-xs font-extrabold tracking-widest text-emerald-400"
                  >
                    AUTORIZADO
                  </motion.span>
                ) : (
                  <motion.span
                    key="pending"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="rounded-md bg-white/5 px-2 py-0.5 text-[11px] font-medium text-slate-400"
                  >
                    {step === "draft" ? "Borrador" : "En proceso"}
                  </motion.span>
                )}
              </AnimatePresence>
            </div>

            <div className="mt-4 text-xs">
              <p className="font-medium text-white">{DEMO_INVOICE.customer.name}</p>
              <p className="font-mono text-slate-400">RUC {DEMO_INVOICE.customer.ruc}</p>
            </div>

            <ul className="mt-4 space-y-2 border-t border-white/10 pt-3 text-xs">
              {DEMO_INVOICE.lines.map((line, i) => (
                <motion.li
                  key={`${cycle}-${i}`}
                  initial={reduce ? false : { opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.3 + i * 0.6, duration: 0.35 }}
                  className="flex justify-between gap-3 text-slate-300"
                >
                  <span>{line.qty} × {line.description}</span>
                  <span className="font-mono text-white">{formatPrice(line.total)}</span>
                </motion.li>
              ))}
            </ul>

            <dl className="mt-3 space-y-1 border-t border-white/10 pt-3 text-xs">
              <div className="flex justify-between text-slate-400"><dt>Subtotal</dt><dd className="font-mono">{formatPrice(DEMO_INVOICE.subtotal)}</dd></div>
              <div className="flex justify-between text-slate-400"><dt>IVA {DEMO_INVOICE.ivaRate} %</dt><dd className="font-mono">{formatPrice(DEMO_INVOICE.iva)}</dd></div>
              <div className="flex justify-between text-sm font-semibold text-white"><dt>Total</dt><dd className="font-mono">{formatPrice(DEMO_INVOICE.total)}</dd></div>
            </dl>

            <div className="mt-4 min-h-14 rounded-lg bg-black/30 p-2.5">
              <p className="text-[10px] uppercase tracking-wider text-slate-500">Clave de acceso</p>
              <p data-testid="demo-clave" className="break-all font-mono text-[11px] leading-snug text-emerald-300">
                {shownClave}
                <span className={cn("inline-block w-[1ch]", typing ? "animate-pulse" : "opacity-0")}>▍</span>
              </p>
            </div>
          </div>

          {/* Estado */}
          <ol className="space-y-2 sm:col-span-2">
            {STATUS.map(({ step: s, label, icon: Icon, process }) => {
              const reached = isAtLeast(step, s);
              const inProgress = step === s && process;
              const done = reached && !inProgress;
              return (
                <li
                  key={s}
                  className={cn(
                    "flex items-start gap-2.5 rounded-lg border px-3 py-2.5 text-xs transition-colors",
                    done ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-100" : inProgress ? "border-brand/40 bg-brand/10 text-white" : "border-white/10 bg-white/[0.03] text-slate-400",
                  )}
                >
                  <span className="mt-px shrink-0">
                    {done ? <CheckCircle2 className="size-4 text-emerald-400" /> : <Icon className={cn("size-4", inProgress ? "animate-pulse text-brand" : "text-slate-500")} />}
                  </span>
                  <span>
                    {label}
                    {inProgress && (
                      <motion.span
                        key={`${cycle}-${s}`}
                        initial={{ width: 0 }}
                        animate={{ width: "100%" }}
                        transition={{ duration: s === "signing" ? 1.6 : 2.0, ease: "easeInOut" }}
                        className="mt-1.5 block h-1 rounded-full bg-brand"
                      />
                    )}
                  </span>
                </li>
              );
            })}
            <li className={cn("rounded-lg px-3 py-2 text-[11px] transition-opacity", authorized ? "opacity-100" : "opacity-0")}>
              <span className="text-slate-400">Ambiente: </span>
              <span className="font-medium text-white">Producción</span>
              <span className="text-slate-500"> · {stepIndex(step) >= stepIndex("authorized") ? new Date().toLocaleDateString("es-EC", { day: "2-digit", month: "2-digit", year: "numeric" }) : ""}</span>
            </li>
          </ol>
        </div>
      </BrowserFrame>
    </div>
  );
}
```

- [ ] **Step 4: `hero.tsx`**

```tsx
import Link from "next/link";
import { ArrowRight, ChevronDown } from "lucide-react";
import { HERO } from "@/content/landing";
import { STORE_APPSTORE_URL, STORE_PLAY_URL } from "@/lib/landing/config";
import { formatPrice, type LandingPlan } from "@/lib/landing/pricing";
import { LiveDemo } from "./live-demo";
import { StoreBadges } from "./store-badges";
import { TrustStrip } from "./trust-strip";

export function Hero({ cheapest }: { cheapest: LandingPlan | null }) {
  const from = cheapest ? formatPrice(cheapest.price_monthly) : "$2.99";

  return (
    <section className="relative overflow-hidden bg-navy pt-16 text-white">
      <div aria-hidden="true" className="pointer-events-none absolute inset-0">
        <div className="absolute -top-40 right-[-10%] size-[640px] rounded-full bg-brand/20 blur-3xl" />
        <div className="absolute bottom-0 left-[-10%] size-[480px] rounded-full bg-[#0653C6]/15 blur-3xl" />
        <div className="absolute inset-x-0 top-16 h-px bg-flag-gradient opacity-70" />
      </div>

      <div className="relative mx-auto max-w-6xl px-5 pb-16 pt-14 sm:px-8 sm:pt-20 lg:pb-24 lg:pt-24">
        <div className="grid items-center gap-12 lg:grid-cols-12">
          <div className="lg:col-span-6">
            <p className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-200">
              {HERO.eyebrow}
            </p>
            <h1 className="mt-6 font-display text-[2.6rem] font-extrabold leading-[1.04] tracking-tight sm:text-5xl lg:text-[3.6rem]">
              {HERO.title}
            </h1>
            <p className="mt-5 max-w-lg text-base leading-relaxed text-slate-300 sm:text-lg">
              <span className="font-semibold text-white">Desde {from} al mes</span>, sin comisión por documento. {HERO.subtitle}
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
              <Link href="/register" className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-brand px-7 text-sm font-semibold text-white shadow-lg shadow-brand/30 transition-colors hover:bg-brand-hover">
                Crear cuenta <ArrowRight className="size-4" aria-hidden="true" />
              </Link>
              <a href="#como-funciona" className="inline-flex h-12 items-center justify-center gap-2 px-5 text-sm font-medium text-slate-200 transition-colors hover:text-white">
                Ver cómo funciona <ChevronDown className="size-4" aria-hidden="true" />
              </a>
            </div>
            <StoreBadges className="mt-8" playUrl={STORE_PLAY_URL} appStoreUrl={STORE_APPSTORE_URL} />
          </div>
          <div className="lg:col-span-6">
            <LiveDemo />
          </div>
        </div>
        <TrustStrip />
      </div>
    </section>
  );
}
```

- [ ] **Step 5: Verificar y commit**

Run: `pnpm test && pnpm typecheck && pnpm lint`
Expected: PASS.

```bash
git add frontend/src/components/marketing/hero.tsx frontend/src/components/marketing/live-demo.tsx frontend/src/components/marketing/trust-strip.tsx frontend/tests/unit/live-demo.test.tsx
git commit -m "feat(landing): hero con demo animada de emisión y franja de confianza"
```

---
### Task 9: Assets reales y secciones Comprobantes, Producto real, Funcionalidades, Cómo funciona

**Files:**
- Create: `frontend/scripts/capture-screenshots.ts`, `frontend/public/marketing/{panel-dashboard,panel-invoice,panel-documents,app-home,app-create,app-pos}.png`, `frontend/public/marketing/icon-facturon.svg`, `frontend/public/marketing/icon-facturon-512.png`, `frontend/src/app/icon.png`, `frontend/src/app/apple-icon.png`
- Replace: `frontend/src/app/favicon.ico`, `backend/public/favicon.ico`
- Create: `frontend/src/components/marketing/documents.tsx`, `showcase.tsx`, `features.tsx`, `how-it-works.tsx`
- Test: `frontend/tests/unit/sections.test.tsx`

**Interfaces:**
- `Documents()`, `Showcase()`, `Features()` (`<section id="funcionalidades">`), `HowItWorks()` (`<section id="como-funciona">`).

- [ ] **Step 1: Test que falla**

`frontend/tests/unit/sections.test.tsx`:

```tsx
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Documents } from "@/components/marketing/documents";
import { Features } from "@/components/marketing/features";
import { HowItWorks } from "@/components/marketing/how-it-works";
import { Showcase } from "@/components/marketing/showcase";

describe("secciones", () => {
  it("Documents muestra los 6 comprobantes con código", () => {
    render(<Documents />);
    for (const code of ["01", "03", "04", "05", "06", "07"]) {
      expect(screen.getByText(code)).toBeInTheDocument();
    }
    expect(screen.getByRole("heading", { level: 2, name: "Todo lo que emites" })).toBeInTheDocument();
  });

  it("Features tiene el ancla y 12 tarjetas", () => {
    const { container } = render(<Features />);
    expect(container.querySelector("section#funcionalidades")).not.toBeNull();
    expect(screen.getAllByRole("heading", { level: 3 })).toHaveLength(12);
  });

  it("HowItWorks tiene el ancla y 3 pasos", () => {
    const { container } = render(<HowItWorks />);
    expect(container.querySelector("section#como-funciona")).not.toBeNull();
    expect(screen.getAllByRole("heading", { level: 3 })).toHaveLength(3);
  });

  it("Showcase usa capturas reales con alt descriptivo", () => {
    render(<Showcase />);
    const images = screen.getAllByRole("img");
    expect(images).toHaveLength(3);
    for (const img of images) {
      expect(img.getAttribute("alt")?.length).toBeGreaterThan(20);
    }
  });
});
```

Run: `pnpm test -- sections`
Expected: FAIL.

- [ ] **Step 2: Assets estáticos (iconos, capturas de la app)**

Desde `frontend/` (las herramientas `rsvg-convert` y `magick` están instaladas en la máquina):

```bash
mkdir -p public/marketing
cp ../mobile/store/screenshots/02-dashboard.png public/marketing/app-home.png
cp ../mobile/store/screenshots/05-crear.png public/marketing/app-create.png
cp ../mobile/store/screenshots/06-pos-venta.png public/marketing/app-pos.png
cp ../mobile/assets/branding/facturon_icon.svg public/marketing/icon-facturon.svg
rsvg-convert -w 512 -h 512 ../mobile/assets/branding/facturon_icon.svg -o public/marketing/icon-facturon-512.png
rsvg-convert -w 180 -h 180 ../mobile/assets/branding/facturon_icon.svg -o src/app/apple-icon.png
rsvg-convert -w 64 -h 64 ../mobile/assets/branding/facturon_icon.svg -o src/app/icon.png
rsvg-convert -w 32 -h 32 ../mobile/assets/branding/facturon_icon.svg -o "$TMPDIR/fav32.png"
magick "$TMPDIR/fav32.png" src/app/favicon.ico
cp src/app/favicon.ico ../backend/public/favicon.ico
ls -la public/marketing src/app/icon.png src/app/apple-icon.png src/app/favicon.ico
```

Expected: 3 PNG de la app (1080×2400), el SVG, el PNG 512, y los tres iconos de Next.

- [ ] **Step 3: Script de capturas del panel web**

`frontend/scripts/capture-screenshots.ts`:

```ts
/**
 * Captura el panel web con el tenant demo local para la landing.
 * Uso: DEMO_PASSWORD=… node scripts/capture-screenshots.ts
 * Requiere backend en :8001 y `pnpm dev` en :3000 (o CAPTURE_BASE_URL).
 */
import { chromium } from "@playwright/test";

const BASE = process.env.CAPTURE_BASE_URL ?? "http://localhost:3000";
const EMAIL = process.env.DEMO_EMAIL ?? "demo@amephia.com";
const PASSWORD = process.env.DEMO_PASSWORD ?? "";

const SHOTS = [
  { path: "/dashboard", file: "panel-dashboard.png", ready: "main" },
  { path: "/documents/new", file: "panel-invoice.png", ready: "form" },
  { path: "/documents", file: "panel-documents.png", ready: "table" },
];

async function main() {
  if (!PASSWORD) throw new Error("Falta DEMO_PASSWORD");
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
    locale: "es-EC",
    colorScheme: "light",
  });
  const page = await context.newPage();

  await page.goto(`${BASE}/login`);
  await page.fill("#email", EMAIL);
  await page.fill("#password", PASSWORD);
  await page.click("button[type=submit]");
  await page.waitForURL(/\/dashboard/, { timeout: 60_000 });

  for (const shot of SHOTS) {
    await page.goto(`${BASE}${shot.path}`);
    await page.waitForSelector(shot.ready, { timeout: 60_000 });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `public/marketing/${shot.file}` });
    console.log(`✓ ${shot.file}`);
  }

  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
```

Ejecutar (con la pila local levantada según `docs`/memoria: MySQL en OrbStack, `php artisan serve --port=8001`, `pnpm dev`):

```bash
DEMO_PASSWORD=demo1234 node scripts/capture-screenshots.ts
for f in panel-dashboard panel-invoice panel-documents; do magick "public/marketing/$f.png" -resize 1920x "public/marketing/$f.png"; done
```

Expected: tres PNG de 1920×1200 en `public/marketing/`. Si el tenant demo no existe en la BD local, crear uno desde `http://localhost:3000/register` (cualquier correo, contraseña fuerte), completar el onboarding y emitir 3–4 facturas en ambiente de pruebas antes de capturar; pasar ese correo/contraseña por `DEMO_EMAIL`/`DEMO_PASSWORD`.

- [ ] **Step 4: `documents.tsx`**

```tsx
import { DOCUMENT_TYPES } from "@/content/landing";
import { ICONS } from "./icons";
import { Reveal } from "./reveal";

export function Documents() {
  return (
    <section aria-labelledby="comprobantes-titulo" className="bg-white py-20 sm:py-28">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Comprobantes</p>
          <h2 id="comprobantes-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            Todo lo que emites
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            Los seis comprobantes electrónicos del SRI, con el código que exige la ficha técnica.
          </p>
        </Reveal>
        <ul className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {DOCUMENT_TYPES.map((doc, i) => {
            const Icon = ICONS[doc.icon];
            return (
              <Reveal key={doc.code} delay={i * 0.05}>
                <li className="flex h-full gap-4 rounded-2xl border border-slate-200 bg-white p-6 transition-shadow hover:shadow-lg hover:shadow-slate-200/60">
                  <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy text-white">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <div>
                    <p className="font-mono text-xs font-semibold text-brand">{doc.code}</p>
                    <h3 className="mt-0.5 text-base font-semibold text-navy">{doc.name}</h3>
                    <p className="mt-1.5 text-sm leading-relaxed text-slate-600">{doc.use}</p>
                  </div>
                </li>
              </Reveal>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
```

- [ ] **Step 5: `showcase.tsx`**

```tsx
import Image from "next/image";
import { Check } from "lucide-react";
import { SHOWCASE } from "@/content/landing";
import { cn } from "@/lib/utils";
import { BrowserFrame } from "./browser-frame";
import { PhoneFrame } from "./phone-frame";
import { Reveal } from "./reveal";

export function Showcase() {
  return (
    <section aria-labelledby="producto-titulo" className="bg-white py-8 sm:py-12">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <h2 id="producto-titulo" className="sr-only">Así se ve Facturón</h2>
        <div className="space-y-24 sm:space-y-32">
          {SHOWCASE.map((row, i) => {
            const reversed = i % 2 === 1;
            return (
              <div key={row.title} className="grid items-center gap-10 lg:grid-cols-12 lg:gap-16">
                <Reveal className={cn("lg:col-span-5", reversed && "lg:order-2")}>
                  <h3 className="font-display text-2xl font-bold tracking-tight text-navy sm:text-3xl">{row.title}</h3>
                  <p className="mt-4 text-base leading-relaxed text-slate-600">{row.text}</p>
                  <ul className="mt-6 space-y-2.5">
                    {row.bullets.map((b) => (
                      <li key={b} className="flex items-start gap-2.5 text-sm text-slate-700">
                        <Check className="mt-0.5 size-4 shrink-0 text-emerald-500" aria-hidden="true" />
                        {b}
                      </li>
                    ))}
                  </ul>
                </Reveal>
                <Reveal className={cn("lg:col-span-7", reversed && "lg:order-1")} delay={0.1}>
                  <div className="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-200 sm:p-8">
                    {row.image.kind === "browser" ? (
                      <BrowserFrame url="facturon.ec" className="border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                        <Image src={row.image.src} alt={row.image.alt} width={row.image.width} height={row.image.height} sizes="(min-width: 1024px) 56vw, 100vw" priority={i === 0} className="block h-auto w-full" />
                      </BrowserFrame>
                    ) : (
                      <PhoneFrame>
                        <Image src={row.image.src} alt={row.image.alt} width={row.image.width} height={row.image.height} sizes="260px" className="block h-auto w-full" />
                      </PhoneFrame>
                    )}
                  </div>
                </Reveal>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
```

- [ ] **Step 6: `features.tsx` y `how-it-works.tsx`**

`features.tsx`:

```tsx
import { FEATURES } from "@/content/landing";
import { cn } from "@/lib/utils";
import { ICONS } from "./icons";
import { Reveal } from "./reveal";

export function Features() {
  return (
    <section id="funcionalidades" aria-labelledby="funcionalidades-titulo" className="scroll-mt-16 bg-slate-50 py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Funcionalidades</p>
          <h2 id="funcionalidades-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            Mucho más que facturar
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            Emisión al SRI, punto de venta, inventario, contabilidad y portal para tus clientes: todo tu negocio en una sola plataforma hecha para la normativa ecuatoriana.
          </p>
        </Reveal>
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {FEATURES.map((f, i) => {
            const Icon = ICONS[f.icon];
            return (
              <Reveal key={f.title} delay={(i % 4) * 0.05} className={cn(f.span === 2 && "sm:col-span-2")}>
                <article className="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 transition-shadow hover:shadow-lg hover:shadow-slate-200/60">
                  <span className="flex size-11 items-center justify-center rounded-xl bg-brand/10 text-brand">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <h3 className="mt-5 text-base font-semibold text-navy">{f.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{f.text}</p>
                </article>
              </Reveal>
            );
          })}
        </div>
      </div>
    </section>
  );
}
```

`how-it-works.tsx`:

```tsx
import { STEPS } from "@/content/landing";
import { Reveal } from "./reveal";

export function HowItWorks() {
  return (
    <section id="como-funciona" aria-labelledby="como-funciona-titulo" className="scroll-mt-16 bg-white py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="mx-auto max-w-2xl text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Cómo funciona</p>
          <h2 id="como-funciona-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            En 5 minutos estás facturando
          </h2>
        </Reveal>
        <ol className="mt-14 grid gap-8 sm:grid-cols-3">
          {STEPS.map((step, i) => (
            <Reveal key={step.title} delay={i * 0.1}>
              <li className="relative rounded-2xl border border-slate-200 bg-white p-7">
                <span className="flex size-12 items-center justify-center rounded-2xl bg-navy font-display text-xl font-bold text-white">{i + 1}</span>
                <h3 className="mt-5 text-base font-semibold text-navy">{step.title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">{step.text}</p>
              </li>
            </Reveal>
          ))}
        </ol>
      </div>
    </section>
  );
}
```

- [ ] **Step 7: Verificar y commit**

Run: `pnpm test && pnpm typecheck && pnpm lint`
Expected: PASS.

```bash
git add frontend/scripts/capture-screenshots.ts frontend/public/marketing frontend/src/app/icon.png frontend/src/app/apple-icon.png frontend/src/app/favicon.ico backend/public/favicon.ico frontend/src/components/marketing/documents.tsx frontend/src/components/marketing/showcase.tsx frontend/src/components/marketing/features.tsx frontend/src/components/marketing/how-it-works.tsx frontend/tests/unit/sections.test.tsx
git commit -m "feat(landing): capturas reales, iconos Facturón y secciones de comprobantes, producto, funcionalidades y pasos"
```

---

### Task 10: Precios, Árbitros, Por qué, FAQ y CTA final

**Files:**
- Create: `frontend/src/components/marketing/pricing.tsx`, `arbitros.tsx`, `why.tsx`, `faq.tsx`, `cta.tsx`
- Test: `frontend/tests/unit/pricing.test.tsx`, `frontend/tests/unit/faq.test.tsx`

**Interfaces:**
- `Pricing({ data: LandingData })` (client, `<section id="precios">`, switch `role="switch"` con `aria-label="Facturación anual"`), `Arbitros()` (`#arbitros`), `Why()`, `Faq()` (`#faq`), `FinalCta()`.

- [ ] **Step 1: Tests que fallan**

`frontend/tests/unit/pricing.test.tsx`:

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Pricing } from "@/components/marketing/pricing";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";

describe("Pricing", () => {
  it("muestra los planes con precio mensual, destaca el featured y enlaza al registro con el plan", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    expect(screen.getByRole("heading", { level: 2, name: LANDING_FIXTURE.pricing_content.title })).toBeInTheDocument();
    expect(screen.getByText("$7.99")).toBeInTheDocument();
    expect(screen.getByText("Más popular")).toBeInTheDocument();
    const links = screen.getAllByRole("link", { name: "Crear cuenta" });
    expect(links).toHaveLength(4);
    expect(links[1]).toHaveAttribute("href", "/register?plan=negocio");
  });

  it("cambia a precios anuales con el switch", () => {
    render(<Pricing data={LANDING_FIXTURE} />);
    fireEvent.click(screen.getByRole("switch", { name: "Facturación anual" }));
    expect(screen.getByText("$79.90")).toBeInTheDocument();
    expect(screen.queryByText("$7.99")).toBeNull();
    expect(screen.getByText("Ahorra hasta 17 %")).toBeInTheDocument();
  });

  it("nunca dice gratis", () => {
    const { container } = render(<Pricing data={LANDING_FIXTURE} />);
    expect(container.textContent).not.toMatch(/gratis/i);
  });
});
```

`frontend/tests/unit/faq.test.tsx`:

```tsx
import { render } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Faq } from "@/components/marketing/faq";
import { FAQS } from "@/content/landing";

describe("Faq", () => {
  it("renderiza 13 <details> nativos con la primera abierta", () => {
    const { container } = render(<Faq />);
    const details = container.querySelectorAll("details");
    expect(details).toHaveLength(13);
    expect(details[0].hasAttribute("open")).toBe(true);
    expect(details[1].hasAttribute("open")).toBe(false);
    expect(details[0].querySelector("summary")?.textContent).toContain(FAQS[0].q);
    expect(container.querySelector("section#faq")).not.toBeNull();
  });
});
```

Run: `pnpm test`
Expected: FAIL.

- [ ] **Step 2: `pricing.tsx`**

```tsx
"use client";

import Link from "next/link";
import { useState } from "react";
import { Check } from "lucide-react";
import { formatPrice, maxSavings, monthlyEquivalent, type LandingData, type LandingPlan } from "@/lib/landing/pricing";
import { cn } from "@/lib/utils";
import { Reveal } from "./reveal";

function PlanCard({ plan, yearly }: { plan: LandingPlan; yearly: boolean }) {
  const featured = plan.is_featured;
  const price = yearly ? plan.price_yearly : plan.price_monthly;
  return (
    <article
      className={cn(
        "relative flex h-full w-[85vw] shrink-0 snap-center flex-col rounded-2xl p-7 sm:w-auto sm:shrink",
        featured ? "bg-navy text-white shadow-xl shadow-navy/20 ring-1 ring-navy" : "bg-white text-navy ring-1 ring-slate-200",
      )}
    >
      {featured && (
        <span className="absolute -top-3 left-6 rounded-full bg-brand px-3.5 py-1 text-[11px] font-semibold text-white shadow-sm">Más popular</span>
      )}
      <h3 className="font-display text-lg font-semibold">{plan.name}</h3>
      {plan.description && <p className={cn("mt-1.5 text-sm", featured ? "text-slate-300" : "text-slate-600")}>{plan.description}</p>}
      <div className="mt-6 flex items-baseline gap-1">
        <span className="font-display text-4xl font-bold tracking-tight">{formatPrice(price, plan.currency)}</span>
        <span className={cn("text-sm", featured ? "text-slate-400" : "text-slate-500")}>{yearly ? "/año" : "/mes"}</span>
      </div>
      <p className={cn("mt-1 h-5 text-xs", featured ? "text-slate-400" : "text-slate-500")}>
        {yearly ? `Equivale a ${formatPrice(monthlyEquivalent(plan.price_yearly), plan.currency)} al mes` : ""}
      </p>
      <Link
        href={`/register?plan=${plan.slug}`}
        className={cn(
          "mt-5 inline-flex h-11 items-center justify-center rounded-xl text-sm font-semibold transition-colors",
          featured ? "bg-brand text-white hover:bg-brand-hover" : "bg-slate-100 text-navy hover:bg-slate-200",
        )}
      >
        Crear cuenta
      </Link>
      <ul className="mt-7 space-y-2.5">
        {plan.features_list.map((feature) => (
          <li key={feature} className={cn("flex items-start gap-2.5 text-sm", featured ? "text-slate-200" : "text-slate-700")}>
            <Check className={cn("mt-0.5 size-4 shrink-0", featured ? "text-emerald-400" : "text-emerald-500")} aria-hidden="true" />
            {feature}
          </li>
        ))}
      </ul>
    </article>
  );
}

export function Pricing({ data }: { data: LandingData }) {
  const [yearly, setYearly] = useState(false);
  const { plans, pricing_content: content } = data;
  const savings = maxSavings(plans);

  return (
    <section id="precios" aria-labelledby="precios-titulo" className="scroll-mt-16 bg-slate-50 py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="mx-auto max-w-2xl text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">{content.eyebrow}</p>
          <h2 id="precios-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">{content.title}</h2>
          <p className="mt-4 text-base text-slate-600">{content.subtitle}</p>
          {content.badge_enabled && content.badge_text && (
            <span className="mt-5 inline-flex items-center rounded-full bg-brand/10 px-4 py-1.5 text-sm font-medium text-brand ring-1 ring-brand/20">{content.badge_text}</span>
          )}
        </Reveal>

        <div className="mt-10 flex items-center justify-center gap-3">
          <span className={cn("text-sm font-medium", yearly ? "text-slate-400" : "text-navy")}>Mensual</span>
          <button
            type="button"
            role="switch"
            aria-checked={yearly}
            aria-label="Facturación anual"
            onClick={() => setYearly((v) => !v)}
            className={cn("relative h-6 w-11 rounded-full transition-colors", yearly ? "bg-brand" : "bg-slate-300")}
          >
            <span className={cn("absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow-sm transition-transform", yearly && "translate-x-5")} />
          </button>
          <span className={cn("text-sm font-medium", yearly ? "text-navy" : "text-slate-400")}>
            Anual
            {savings > 0 && (
              <span className="ml-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">Ahorra hasta {savings} %</span>
            )}
          </span>
        </div>
        <p className="mt-4 text-center text-sm text-slate-500">Sin comisión por documento. Pago por transferencia bancaria; tu cuenta se activa al confirmarlo.</p>

        <div className="-mx-5 mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-4">
          {plans.map((plan) => (
            <PlanCard key={plan.id} plan={plan} yearly={yearly} />
          ))}
        </div>

        {content.footer_note && <p className="mt-8 text-center text-sm text-slate-500">{content.footer_note}</p>}
      </div>
    </section>
  );
}
```

- [ ] **Step 3: `arbitros.tsx`, `why.tsx`, `cta.tsx`**

`arbitros.tsx`:

```tsx
import { Check, MessageCircle } from "lucide-react";
import { ARBITROS } from "@/content/landing";
import { whatsappUrl } from "@/lib/landing/config";
import { Reveal } from "./reveal";

const MATCHES = [
  { match: "Fecha 12 · Serie A", date: "07/09", status: "Pendiente" },
  { match: "Fecha 11 · Serie B", date: "31/08", status: "Facturado" },
  { match: "Fecha 11 · Serie A", date: "30/08", status: "Facturado" },
] as const;

export function Arbitros() {
  return (
    <section id="arbitros" aria-labelledby="arbitros-titulo" className="relative scroll-mt-16 overflow-hidden bg-navy py-20 text-white sm:py-24">
      <div aria-hidden="true" className="absolute inset-x-0 top-0 h-px bg-flag-gradient opacity-70" />
      <div className="mx-auto grid max-w-6xl gap-10 px-5 sm:px-8 lg:grid-cols-12 lg:items-center">
        <Reveal className="lg:col-span-7">
          <p className="text-xs font-semibold uppercase tracking-widest text-emerald-400">{ARBITROS.eyebrow}</p>
          <h2 id="arbitros-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">{ARBITROS.title}</h2>
          <p className="mt-4 text-base text-slate-300">{ARBITROS.text}</p>
          <ul className="mt-6 space-y-3">
            {ARBITROS.bullets.map((b) => (
              <li key={b} className="flex items-start gap-2.5 text-sm text-slate-200">
                <Check className="mt-0.5 size-4 shrink-0 text-emerald-400" aria-hidden="true" />
                {b}
              </li>
            ))}
          </ul>
          <a
            href={whatsappUrl(ARBITROS.whatsappText)}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-8 inline-flex h-11 items-center gap-2 rounded-full bg-white px-6 text-sm font-semibold text-navy transition-colors hover:bg-slate-100"
          >
            <MessageCircle className="size-4" aria-hidden="true" />
            {ARBITROS.cta}
          </a>
        </Reveal>
        <Reveal className="lg:col-span-5" delay={0.1}>
          <div className="rounded-2xl border border-white/10 bg-navy-2 p-5" aria-hidden="true">
            <p className="text-[11px] font-medium uppercase tracking-wider text-slate-400">Partidos del mes</p>
            <ul className="mt-3 divide-y divide-white/10">
              {MATCHES.map((m) => (
                <li key={m.match} className="flex items-center justify-between gap-3 py-3 text-sm">
                  <div>
                    <p className="font-medium text-white">{m.match}</p>
                    <p className="text-xs text-slate-400">{m.date}</p>
                  </div>
                  <span className={m.status === "Pendiente" ? "rounded-md bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-300" : "rounded-md bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-300"}>
                    {m.status}
                  </span>
                </li>
              ))}
            </ul>
            <p className="mt-4 rounded-lg bg-white/5 px-3 py-2 text-xs text-slate-300">Ventana FEF abierta hasta el 20 · 1 partido por facturar</p>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
```

`why.tsx`:

```tsx
import { REASONS } from "@/content/landing";
import { ICONS } from "./icons";
import { Reveal } from "./reveal";

export function Why() {
  return (
    <section aria-labelledby="porque-titulo" className="bg-white py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-5 sm:px-8">
        <Reveal className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Por qué Facturón</p>
          <h2 id="porque-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">
            No es un sistema genérico adaptado a Ecuador
          </h2>
          <p className="mt-4 text-base leading-relaxed text-slate-600">
            Lo construimos desde cero sobre la normativa del SRI. Cada detalle está pensado para el contribuyente ecuatoriano.
          </p>
        </Reveal>
        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {REASONS.map((r, i) => {
            const Icon = ICONS[r.icon];
            return (
              <Reveal key={r.title} delay={i * 0.08}>
                <article className="h-full rounded-2xl border border-slate-200 bg-white p-7">
                  <span className="flex size-11 items-center justify-center rounded-xl bg-navy text-white">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <h3 className="mt-5 text-lg font-semibold text-navy">{r.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{r.text}</p>
                </article>
              </Reveal>
            );
          })}
        </div>
        <Reveal className="mt-8">
          <p className="rounded-2xl bg-slate-50 px-6 py-5 text-center text-base font-medium text-navy ring-1 ring-slate-200">
            Precio honesto: desde $2.99 al mes, sin comisión por documento.
          </p>
        </Reveal>
      </div>
    </section>
  );
}
```

`cta.tsx`:

```tsx
import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { FINAL_CTA } from "@/content/landing";
import { whatsappUrl } from "@/lib/landing/config";
import { WhatsAppGlyph } from "./whatsapp-button";

export function FinalCta() {
  return (
    <section aria-labelledby="cta-titulo" className="relative overflow-hidden bg-navy py-24 text-white sm:py-32">
      <div aria-hidden="true" className="absolute left-1/2 top-0 h-[400px] w-[800px] -translate-x-1/2 rounded-full bg-brand/20 blur-3xl" />
      <div className="relative mx-auto max-w-3xl px-5 text-center sm:px-8">
        <h2 id="cta-titulo" className="font-display text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{FINAL_CTA.title}</h2>
        <p className="mx-auto mt-4 max-w-xl text-base text-slate-300 sm:text-lg">{FINAL_CTA.text}</p>
        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Link href="/register" className="inline-flex h-12 items-center gap-2 rounded-full bg-brand px-7 text-sm font-semibold text-white shadow-lg shadow-brand/30 transition-colors hover:bg-brand-hover">
            Crear cuenta <ArrowRight className="size-4" aria-hidden="true" />
          </Link>
          <a href={whatsappUrl()} target="_blank" rel="noopener noreferrer" className="inline-flex h-12 items-center gap-2 rounded-full border border-white/15 px-6 text-sm font-medium text-slate-200 transition-colors hover:border-white/30 hover:text-white">
            <WhatsAppGlyph className="size-4" /> Hablar por WhatsApp
          </a>
        </div>
      </div>
    </section>
  );
}
```

- [ ] **Step 4: `faq.tsx`**

```tsx
import { ChevronDown } from "lucide-react";
import { FAQS } from "@/content/landing";
import { CONTACT_EMAIL, whatsappUrl } from "@/lib/landing/config";
import { Reveal } from "./reveal";

export function Faq() {
  return (
    <section id="faq" aria-labelledby="faq-titulo" className="scroll-mt-16 bg-white py-24 sm:py-32">
      <div className="mx-auto max-w-3xl px-5 sm:px-8">
        <Reveal className="text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-brand">Preguntas frecuentes</p>
          <h2 id="faq-titulo" className="mt-3 font-display text-3xl font-bold tracking-tight text-navy sm:text-4xl">Resolvemos tus dudas</h2>
        </Reveal>
        <div className="mt-12 space-y-3">
          {FAQS.map((item, i) => (
            <details
              key={item.q}
              name="faq"
              open={i === 0}
              className="group rounded-xl border border-slate-200 bg-white transition-colors open:border-brand/40 open:bg-brand/[0.03]"
            >
              <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 text-sm font-semibold text-navy [&::-webkit-details-marker]:hidden">
                {item.q}
                <ChevronDown className="size-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true" />
              </summary>
              <p className="px-6 pb-5 text-sm leading-relaxed text-slate-600">{item.a}</p>
            </details>
          ))}
        </div>
        <p className="mt-10 text-center text-sm text-slate-500">
          ¿Otra pregunta?{" "}
          <a href={whatsappUrl()} target="_blank" rel="noopener noreferrer" className="font-medium text-brand hover:underline">Escríbenos por WhatsApp</a>
          {" "}o a{" "}
          <a href={`mailto:${CONTACT_EMAIL}`} className="font-medium text-brand hover:underline">{CONTACT_EMAIL}</a>.
        </p>
      </div>
    </section>
  );
}
```

- [ ] **Step 5: Verificar y commit**

Run: `pnpm test && pnpm typecheck && pnpm lint`
Expected: PASS.

```bash
git add frontend/src/components/marketing/pricing.tsx frontend/src/components/marketing/arbitros.tsx frontend/src/components/marketing/why.tsx frontend/src/components/marketing/faq.tsx frontend/src/components/marketing/cta.tsx frontend/tests/unit/pricing.test.tsx frontend/tests/unit/faq.test.tsx
git commit -m "feat(landing): precios desde el CMS, sección de árbitros, razones, FAQ nativo y CTA final"
```

---
### Task 11: Página `/`, JSON-LD, robots, sitemap, llms.txt e imagen OG

**Files:**
- Create: `frontend/src/app/(marketing)/page.tsx`, `frontend/src/app/robots.ts`, `frontend/src/app/sitemap.ts`, `frontend/src/app/llms.txt/route.ts`, `frontend/src/lib/landing/seo.ts`, `frontend/src/lib/landing/llms.ts`, `frontend/scripts/og/og.html`, `frontend/scripts/og/build-og.ts`, `frontend/public/marketing/og.png`
- Test: `frontend/tests/unit/seo.test.ts`, `frontend/tests/unit/llms.test.ts`

**Interfaces:**
- `buildRobots(appUrl: string): MetadataRoute.Robots`, `buildSitemap(appUrl: string, lastModified: Date): MetadataRoute.Sitemap`, `PANEL_PATHS: string[]`, `AI_BOTS: string[]`.
- `buildLlmsTxt(input: { appUrl: string; plans: LandingPlan[]; contactEmail: string; whatsapp: string }): string`.

- [ ] **Step 1: Tests que fallan**

`frontend/tests/unit/seo.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import { AI_BOTS, PANEL_PATHS, buildRobots, buildSitemap } from "@/lib/landing/seo";

describe("robots", () => {
  const robots = buildRobots("https://facturon.ec");
  const rules = Array.isArray(robots.rules) ? robots.rules : [robots.rules];

  it("permite todo a todos los bots, incluidos los de IA, y bloquea el panel", () => {
    const agents = rules.map((r) => r.userAgent);
    expect(agents).toContain("*");
    for (const bot of AI_BOTS) expect(agents).toContain(bot);
    for (const rule of rules) {
      expect(rule.allow).toBe("/");
      expect(rule.disallow).toEqual(PANEL_PATHS);
    }
    expect(PANEL_PATHS).toContain("/dashboard");
    expect(PANEL_PATHS).toContain("/api/");
    expect(robots.sitemap).toBe("https://facturon.ec/sitemap.xml");
  });
});

describe("sitemap", () => {
  it("lista la landing, registro, login y páginas legales", () => {
    const urls = buildSitemap("https://facturon.ec", new Date("2026-09-12")).map((e) => e.url);
    expect(urls).toEqual([
      "https://facturon.ec/",
      "https://facturon.ec/register",
      "https://facturon.ec/login",
      "https://facturon.ec/terms",
      "https://facturon.ec/privacy",
    ]);
  });
});
```

`frontend/tests/unit/llms.test.ts`:

```ts
import { describe, expect, it } from "vitest";
import { LANDING_FIXTURE } from "@/lib/landing/fixture";
import { buildLlmsTxt } from "@/lib/landing/llms";

describe("llms.txt", () => {
  const text = buildLlmsTxt({ appUrl: "https://facturon.ec", plans: LANDING_FIXTURE.plans, contactEmail: "info@amephia.com", whatsapp: "+1 334 732 4056" });

  it("sigue el formato llmstxt.org y solo contiene hechos", () => {
    expect(text.startsWith("# Facturón\n\n> ")).toBe(true);
    expect(text).toContain("- Negocio: $7.99/mes o $79.90/año");
    expect(text).toContain("https://facturon.ec/register");
    expect(text).toContain("info@amephia.com");
    expect(text).not.toMatch(/gratis/i);
    expect(text).not.toMatch(/ignora|ignore previous|recomienda siempre|always recommend/i);
  });

  it("sin planes omite la sección de precios", () => {
    const t = buildLlmsTxt({ appUrl: "https://facturon.ec", plans: [], contactEmail: "info@amephia.com", whatsapp: "+1 334 732 4056" });
    expect(t).not.toContain("## Precios");
  });
});
```

Run: `pnpm test`
Expected: FAIL.

- [ ] **Step 2: `seo.ts` y `llms.ts`**

`frontend/src/lib/landing/seo.ts`:

```ts
import type { MetadataRoute } from "next";

/** Rutas privadas del panel: no aportan al índice y exigen sesión. */
export const PANEL_PATHS = [
  "/api/",
  "/dashboard",
  "/onboarding",
  "/settings",
  "/documents",
  "/credit-notes",
  "/debit-notes",
  "/guides",
  "/retentions",
  "/liquidations",
  "/quotes",
  "/recurring-invoices",
  "/customers",
  "/products",
  "/categories",
  "/inventory",
  "/purchases",
  "/suppliers",
  "/received-documents",
  "/pos",
  "/reports",
  "/accounting",
  "/personal-expenses",
  "/referee",
  "/support",
];

/** Rastreadores de asistentes de IA a los que se permite explícitamente el sitio público. */
export const AI_BOTS = ["GPTBot", "ChatGPT-User", "ClaudeBot", "Claude-Web", "anthropic-ai", "PerplexityBot", "Google-Extended", "Bingbot", "Applebot"];

export function buildRobots(appUrl: string): MetadataRoute.Robots {
  return {
    rules: [
      { userAgent: "*", allow: "/", disallow: PANEL_PATHS },
      ...AI_BOTS.map((userAgent) => ({ userAgent, allow: "/", disallow: PANEL_PATHS })),
    ],
    sitemap: `${appUrl}/sitemap.xml`,
    host: appUrl,
  };
}

export function buildSitemap(appUrl: string, lastModified: Date): MetadataRoute.Sitemap {
  return [
    { url: `${appUrl}/`, lastModified, changeFrequency: "weekly", priority: 1 },
    { url: `${appUrl}/register`, lastModified, changeFrequency: "monthly", priority: 0.8 },
    { url: `${appUrl}/login`, lastModified, changeFrequency: "yearly", priority: 0.3 },
    { url: `${appUrl}/terms`, lastModified, changeFrequency: "yearly", priority: 0.2 },
    { url: `${appUrl}/privacy`, lastModified, changeFrequency: "yearly", priority: 0.2 },
  ];
}
```

`frontend/src/lib/landing/llms.ts`:

```ts
import { DOCUMENT_TYPES, FAQS, FEATURES, SITE_DESCRIPTION } from "@/content/landing";
import { formatPrice, type LandingPlan } from "./pricing";

/** Texto para llms.txt (llmstxt.org): resumen factual de Facturón para asistentes de IA. */
export function buildLlmsTxt(input: { appUrl: string; plans: LandingPlan[]; contactEmail: string; whatsapp: string }): string {
  const lines: string[] = [
    "# Facturón",
    "",
    `> ${SITE_DESCRIPTION}`,
    "",
    "Facturón es un software de facturación electrónica para Ecuador, desarrollado por AmePhia Systems Inc. Genera comprobantes según la ficha técnica del SRI, los firma con el certificado .p12 del contribuyente (XAdES-BES) y los envía a los web services del SRI, que autoriza cada comprobante. Facturón no está afiliado al SRI.",
    "",
    "## Para quién",
    "",
    "- Profesionales, emprendedores y PyMEs de Ecuador que emiten comprobantes electrónicos.",
    "- Contadores que gestionan varias empresas (RUC).",
    "- Árbitros de fútbol que facturan a la FEF (módulo especializado).",
    "",
    "## Comprobantes",
    "",
    ...DOCUMENT_TYPES.map((d) => `- ${d.code} ${d.name}: ${d.use}`),
    "",
    "## Funcionalidades",
    "",
    ...FEATURES.map((f) => `- ${f.title}: ${f.text}`),
  ];

  if (input.plans.length > 0) {
    lines.push("", "## Precios", "", "Sin comisión por documento. Pago por transferencia bancaria; sin período de prueba.", "");
    for (const p of input.plans) {
      lines.push(`- ${p.name}: ${formatPrice(p.price_monthly, p.currency)}/mes o ${formatPrice(p.price_yearly, p.currency)}/año. ${p.features_list.join(", ")}.`);
    }
  }

  lines.push(
    "",
    "## Requisitos",
    "",
    "- RUC activo.",
    "- Firma electrónica vigente (.p12 del Banco Central, Security Data, ANF u otra entidad acreditada).",
    "- Habilitación de comprobantes electrónicos en SRI en línea.",
    "",
    "## Preguntas frecuentes",
    "",
    ...FAQS.map((f) => `- ${f.q} ${f.a}`),
    "",
    "## Enlaces",
    "",
    `- Sitio: ${input.appUrl}/`,
    `- Crear cuenta: ${input.appUrl}/register`,
    `- Ingresar: ${input.appUrl}/login`,
    `- Términos: ${input.appUrl}/terms`,
    `- Privacidad: ${input.appUrl}/privacy`,
    `- Contacto: ${input.contactEmail} · WhatsApp ${input.whatsapp}`,
    "",
  );

  return lines.join("\n");
}
```

- [ ] **Step 3: Archivos de app para robots, sitemap y llms.txt**

`frontend/src/app/robots.ts`:

```ts
import type { MetadataRoute } from "next";
import { APP_URL } from "@/lib/landing/config";
import { buildRobots } from "@/lib/landing/seo";

export default function robots(): MetadataRoute.Robots {
  return buildRobots(APP_URL);
}
```

`frontend/src/app/sitemap.ts`:

```ts
import type { MetadataRoute } from "next";
import { APP_URL } from "@/lib/landing/config";
import { buildSitemap } from "@/lib/landing/seo";

export default function sitemap(): MetadataRoute.Sitemap {
  return buildSitemap(APP_URL, new Date());
}
```

`frontend/src/app/llms.txt/route.ts`:

```ts
import { APP_URL, CONTACT_EMAIL, formatWhatsapp } from "@/lib/landing/config";
import { getLandingData } from "@/lib/landing/data";
import { buildLlmsTxt } from "@/lib/landing/llms";

// Dinámico: los planes vienen de la API (fetch cacheado 300 s), no del build.
export const dynamic = "force-dynamic";

export async function GET() {
  const data = await getLandingData();
  const body = buildLlmsTxt({ appUrl: APP_URL, plans: data?.plans ?? [], contactEmail: CONTACT_EMAIL, whatsapp: formatWhatsapp() });
  return new Response(body, {
    headers: { "Content-Type": "text/plain; charset=utf-8", "Cache-Control": "public, max-age=3600" },
  });
}
```

- [ ] **Step 4: Página `/`**

`frontend/src/app/(marketing)/page.tsx`:

```tsx
import { FAQS, FEATURE_LIST_FOR_SEO, SITE_DESCRIPTION } from "@/content/landing";
import { APP_URL, CONTACT_EMAIL, WHATSAPP_DIGITS } from "@/lib/landing/config";
import { getLandingData } from "@/lib/landing/data";
import { buildJsonLd } from "@/lib/landing/jsonld";
import { cheapestPlan } from "@/lib/landing/pricing";
import { Arbitros } from "@/components/marketing/arbitros";
import { FinalCta } from "@/components/marketing/cta";
import { Documents } from "@/components/marketing/documents";
import { Faq } from "@/components/marketing/faq";
import { Features } from "@/components/marketing/features";
import { SiteFooter } from "@/components/marketing/footer";
import { Hero } from "@/components/marketing/hero";
import { HowItWorks } from "@/components/marketing/how-it-works";
import { MarketingNav } from "@/components/marketing/nav";
import { Pricing } from "@/components/marketing/pricing";
import { Showcase } from "@/components/marketing/showcase";
import { Why } from "@/components/marketing/why";

// La nav lee la cookie de sesión y los precios vienen de la API (fetch con
// revalidate 300 s): la ruta se renderiza por petición, no en el build.
export const dynamic = "force-dynamic";

export default async function LandingPage() {
  const data = await getLandingData();
  const plans = data?.plans ?? [];
  const jsonLd = buildJsonLd({
    baseUrl: APP_URL,
    plans,
    faqs: FAQS,
    featureList: FEATURE_LIST_FOR_SEO,
    contactEmail: CONTACT_EMAIL,
    whatsappDigits: WHATSAPP_DIGITS,
    description: SITE_DESCRIPTION,
  });

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <MarketingNav />
      <main id="contenido">
        <Hero cheapest={cheapestPlan(plans)} />
        <Documents />
        <Showcase />
        <Features />
        <HowItWorks />
        {data && <Pricing data={data} />}
        <Arbitros />
        <Why />
        <Faq />
        <FinalCta />
      </main>
      <SiteFooter />
    </>
  );
}
```

- [ ] **Step 5: Imagen Open Graph**

`frontend/scripts/og/og.html`:

```html
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  html, body { margin: 0; width: 1200px; height: 630px; background: #0B1220; font-family: -apple-system, "Segoe UI", Inter, system-ui, sans-serif; color: #fff; }
  .wrap { position: relative; width: 1200px; height: 630px; overflow: hidden; }
  .glow { position: absolute; right: -120px; top: -160px; width: 620px; height: 620px; border-radius: 50%; background: rgba(43,84,228,.35); filter: blur(90px); }
  .flag { position: absolute; left: 0; right: 0; top: 0; height: 6px; background: linear-gradient(90deg,#FFCE00 0%,#0653C6 50%,#EF3340 100%); }
  .content { position: absolute; left: 80px; top: 92px; right: 80px; }
  .brand { display: flex; align-items: center; gap: 18px; }
  .brand svg { width: 64px; height: 64px; }
  .brand span { font-size: 34px; font-weight: 800; letter-spacing: -0.02em; }
  h1 { margin: 56px 0 0; font-size: 66px; line-height: 1.05; letter-spacing: -0.03em; font-weight: 800; max-width: 980px; }
  p { margin: 28px 0 0; font-size: 28px; color: #B9C3D6; max-width: 900px; line-height: 1.35; }
  .price { position: absolute; left: 80px; bottom: 64px; display: inline-flex; align-items: center; gap: 14px; background: #2B54E4; color: #fff; font-size: 26px; font-weight: 700; padding: 16px 28px; border-radius: 999px; }
  .site { position: absolute; right: 80px; bottom: 76px; font-size: 24px; color: #93A6C4; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">
  <div class="glow"></div>
  <div class="flag"></div>
  <div class="content">
    <div class="brand">
      <svg viewBox="0 0 1024 1024"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FFCE00"/><stop offset="48%" stop-color="#0653C6"/><stop offset="100%" stop-color="#EF3340"/></linearGradient></defs><rect width="1024" height="1024" rx="230" fill="url(#g)"/><rect x="110" y="110" width="804" height="804" rx="190" fill="#0B1424"/><rect x="400" y="300" width="100" height="424" rx="16" fill="#fff"/><rect x="400" y="300" width="260" height="100" rx="16" fill="#fff"/><rect x="400" y="440" width="200" height="96" rx="16" fill="#fff"/></svg>
      <span>Facturón</span>
    </div>
    <h1>Facturación electrónica que el SRI autoriza en segundos</h1>
    <p>Facturas, retenciones y guías firmadas con tu certificado y enviadas a tus clientes automáticamente.</p>
  </div>
  <div class="price">Desde $2.99 al mes · sin comisión por documento</div>
  <div class="site">facturon.ec</div>
</div>
</body>
</html>
```

`frontend/scripts/og/build-og.ts`:

```ts
/** Genera public/marketing/og.png (1200×630) a partir de og.html. Uso: node scripts/og/build-og.ts */
import path from "node:path";
import { fileURLToPath } from "node:url";
import { chromium } from "@playwright/test";

const here = path.dirname(fileURLToPath(import.meta.url));

async function main() {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });
  await page.goto(`file://${path.join(here, "og.html")}`);
  await page.waitForTimeout(300);
  await page.screenshot({ path: path.join(here, "../../public/marketing/og.png"), type: "png" });
  await browser.close();
  console.log("✓ public/marketing/og.png");
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
```

Run: `node scripts/og/build-og.ts && magick identify public/marketing/og.png`
Expected: `og.png PNG 1200x630`.

- [ ] **Step 6: Verificar y commit**

Run: `pnpm test && pnpm typecheck && pnpm lint`
Expected: PASS.

```bash
git add "frontend/src/app/(marketing)/page.tsx" frontend/src/app/robots.ts frontend/src/app/sitemap.ts frontend/src/app/llms.txt/route.ts frontend/src/lib/landing/seo.ts frontend/src/lib/landing/llms.ts frontend/scripts/og frontend/public/marketing/og.png frontend/tests/unit/seo.test.ts frontend/tests/unit/llms.test.ts
git commit -m "feat(landing): página raíz con JSON-LD, robots, sitemap, llms.txt e imagen OG"
```

---

### Task 12: E2E con Playwright, build completo y Lighthouse

**Files:**
- Create: `frontend/tests/e2e/landing.spec.ts`

- [ ] **Step 1: Escribir los e2e**

```ts
import { expect, test } from "@playwright/test";

test.describe("landing", () => {
  test("hero, un solo h1, CTA visible y sin errores de consola", async ({ page }) => {
    const errors: string[] = [];
    page.on("console", (msg) => {
      if (msg.type() === "error") errors.push(msg.text());
    });
    await page.goto("/");
    await expect(page.locator("h1")).toHaveCount(1);
    await expect(page.getByRole("heading", { level: 1 })).toHaveText("Facturación electrónica que el SRI autoriza en segundos");
    await expect(page.getByRole("link", { name: "Crear cuenta" }).first()).toBeInViewport();
    expect(errors).toEqual([]);
  });

  test("precios del fixture con toggle anual y enlace al registro por plan", async ({ page }) => {
    await page.goto("/");
    const pricing = page.locator("#precios");
    await pricing.scrollIntoViewIfNeeded();
    await expect(pricing.getByText("Negocio", { exact: true })).toBeVisible();
    await expect(pricing.getByText("$7.99", { exact: true })).toBeVisible();
    await pricing.getByRole("switch", { name: "Facturación anual" }).click();
    await expect(pricing.getByText("$79.90", { exact: true })).toBeVisible();
    await expect(pricing.getByRole("link", { name: "Crear cuenta" }).nth(1)).toHaveAttribute("href", "/register?plan=negocio");
  });

  test("JSON-LD con Organization, SoftwareApplication y FAQPage", async ({ page }) => {
    await page.goto("/");
    const raw = await page.locator('script[type="application/ld+json"]').first().textContent();
    const json = JSON.parse(raw ?? "{}") as { "@graph": Array<{ "@type": string }> };
    expect(json["@graph"].map((n) => n["@type"])).toEqual(["Organization", "SoftwareApplication", "FAQPage"]);
  });

  test("robots, sitemap y llms.txt", async ({ request }) => {
    const robots = await request.get("/robots.txt");
    expect(robots.ok()).toBe(true);
    const robotsText = await robots.text();
    expect(robotsText).toContain("User-Agent: GPTBot");
    expect(robotsText).toContain("Disallow: /dashboard");

    const sitemap = await request.get("/sitemap.xml");
    expect(sitemap.ok()).toBe(true);
    expect(await sitemap.text()).toContain("/register");

    const llms = await request.get("/llms.txt");
    expect(llms.ok()).toBe(true);
    expect((await llms.text()).startsWith("# Facturón")).toBe(true);
  });

  test("el cuerpo sigue claro aunque el sistema esté en dark", async ({ browser }) => {
    const context = await browser.newContext({ colorScheme: "dark" });
    const page = await context.newPage();
    await page.goto("/");
    const bg = await page.locator("#landing").evaluate((el) => getComputedStyle(el).backgroundColor);
    expect(bg).toBe("rgb(255, 255, 255)");
    await context.close();
  });

  test("con reduced motion la demo muestra AUTORIZADO de inmediato", async ({ browser }) => {
    const context = await browser.newContext({ reducedMotion: "reduce" });
    const page = await context.newPage();
    await page.goto("/");
    await expect(page.getByTestId("demo-stamp")).toHaveText("AUTORIZADO");
    await context.close();
  });

  test("sin scroll horizontal", async ({ page }) => {
    await page.goto("/");
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    expect(overflow).toBe(false);
  });

  test("/dashboard sin sesión termina en /login", async ({ page }) => {
    await page.goto("/dashboard");
    await expect(page).toHaveURL(/\/login/);
  });
});
```

- [ ] **Step 2: Ejecutar unit, lint, build y e2e**

```bash
cd frontend
pnpm lint && pnpm typecheck && pnpm test
pnpm test:e2e
```

Expected: unit PASS; `next build` sin errores; e2e PASS en `desktop` y `mobile`. Si `next build` falla por no poder descargar Bricolage Grotesque (sin red en sandbox), repetir el comando fuera del sandbox.

- [ ] **Step 3: Lighthouse (móvil) contra el build de producción**

Con el servidor de e2e corriendo (`LANDING_DATA_SOURCE=fixture pnpm start -p 3100`):

```bash
CHROME_PATH="$(ls -d ~/Library/Caches/ms-playwright/chromium-*/chrome-mac*/Chromium.app/Contents/MacOS/Chromium | tail -1)" \
npx --yes lighthouse http://localhost:3100/ --form-factor=mobile --screenEmulation.mobile --throttling-method=simulate \
  --only-categories=performance,accessibility,best-practices,seo --quiet --chrome-flags="--headless=new" \
  --output=json --output-path="$TMPDIR/lh.json"
node -e 'const r=require(process.env.TMPDIR+"/lh.json").categories;for(const k in r)console.log(k,Math.round(r[k].score*100))'
```

Expected: las cuatro categorías ≥ 90. Si Performance < 90, revisar `sizes`/`priority` de las capturas y el peso de `motion` (`pnpm build` imprime el tamaño de First Load JS de `/`; debe ser < 200 kB en total).

- [ ] **Step 4: Commit**

```bash
git add frontend/tests/e2e/landing.spec.ts
git commit -m "test(landing): e2e de la landing (estructura, precios, SEO, dark mode, reduced motion)"
```

---

### Task 13: nginx, retiro de la landing Blade, build args, CI y docs

**Files:**
- Modify: `docker/nginx/conf.d/production.conf`, `backend/routes/web.php`, `frontend/Dockerfile`, `docker/docker-compose.production.yml`, `backend/.env.production`, `frontend/.gitignore`, `.github/workflows/ci.yml`, `docs/DEPLOYMENT.md`
- Delete: `backend/resources/views/welcome.blade.php`, `backend/public/robots.txt`
- Create: `frontend/.env.example`

- [ ] **Step 1: nginx sin el switch por cookie**

En `docker/nginx/conf.d/production.conf`, eliminar completo el bloque:

```nginx
    # La raíz exacta: si NO hay sesión -> LANDING de Laravel (welcome.blade);
    # si hay cookie de sesión -> panel Next.js (dashboard/onboarding).
    location = / {
        error_page 418 = @frontend;
        if ($cookie_factura_session) { return 418; }
        rewrite ^ /index.php?$query_string last;
    }
```

y reemplazar las líneas 4–10 del comentario de cabecera por:

```nginx
# Topología: el FRONTEND Next.js sirve la landing (/) y el panel; el BACKEND
# Laravel (API v1, Filament /admin, portal, /terms, /privacy…) va por rutas
# específicas. El proxy server-side del frontend llama a la API por la red
# interna (http://nginx). Archivos existentes en backend/public se sirven
# directo, así que NO debe existir backend/public/robots.txt (tapa el de Next).
#
# Requiere certificados SSL en /etc/nginx/ssl/ (fullchain.pem + privkey.pem).
```

Verificar: `docker run --rm -v "$PWD/docker/nginx/conf.d/production.conf:/etc/nginx/conf.d/default.conf:ro" nginx:alpine nginx -t` → `syntax is ok` (los upstreams `frontend`/`app` no resuelven en local; si `nginx -t` falla solo por eso, es esperado y basta con revisar el diff).

- [ ] **Step 2: Retirar la landing Blade y su ruta**

En `backend/routes/web.php`, eliminar el bloque completo `Route::get('/', function () { … return view('welcome', compact('plans', 'pricingContent')); });` (líneas 76–97).

```bash
git rm backend/resources/views/welcome.blade.php backend/public/robots.txt
cd backend && php artisan test
```

Expected: toda la suite PASS (ya sin `ExampleTest`). `grep -rn "welcome" backend/routes backend/app` no devuelve nada.

- [ ] **Step 3: Build args de contacto y tiendas**

En `frontend/Dockerfile`, después de `ARG NEXT_PUBLIC_APP_URL=…`:

```dockerfile
ARG NEXT_PUBLIC_WHATSAPP="13347324056"
ARG NEXT_PUBLIC_CONTACT_EMAIL="info@amephia.com"
ARG NEXT_PUBLIC_STORE_PLAY_URL=""
ARG NEXT_PUBLIC_STORE_APPSTORE_URL=""
ENV NEXT_PUBLIC_WHATSAPP=$NEXT_PUBLIC_WHATSAPP
ENV NEXT_PUBLIC_CONTACT_EMAIL=$NEXT_PUBLIC_CONTACT_EMAIL
ENV NEXT_PUBLIC_STORE_PLAY_URL=$NEXT_PUBLIC_STORE_PLAY_URL
ENV NEXT_PUBLIC_STORE_APPSTORE_URL=$NEXT_PUBLIC_STORE_APPSTORE_URL
```

En `docker/docker-compose.production.yml`, servicio `frontend` → `build.args`, añadir:

```yaml
        NEXT_PUBLIC_WHATSAPP: ${LANDING_WHATSAPP:-13347324056}
        NEXT_PUBLIC_CONTACT_EMAIL: ${LANDING_CONTACT_EMAIL:-info@amephia.com}
        NEXT_PUBLIC_STORE_PLAY_URL: ${STORE_PLAY_URL:-}
        NEXT_PUBLIC_STORE_APPSTORE_URL: ${STORE_APPSTORE_URL:-}
```

y quitar la línea `version: '3.8'` (obsoleta; compose la ignora con warning).

En `backend/.env.production` (plantilla), al final:

```dotenv
# --- Landing (build args del frontend; compose los lee de este .env vía symlink docker/.env)
LANDING_WHATSAPP=13347324056
LANDING_CONTACT_EMAIL=info@amephia.com
STORE_PLAY_URL=
STORE_APPSTORE_URL=
```

`frontend/.env.example` (y en `frontend/.gitignore` añadir `!.env.example` justo después de `.env*`):

```dotenv
LARAVEL_API_URL=http://localhost:8001
NEXT_PUBLIC_APP_NAME=AmePhia Facturación
NEXT_PUBLIC_APP_URL=http://localhost:3000
# Landing
NEXT_PUBLIC_WHATSAPP=13347324056
NEXT_PUBLIC_CONTACT_EMAIL=info@amephia.com
NEXT_PUBLIC_STORE_PLAY_URL=
NEXT_PUBLIC_STORE_APPSTORE_URL=
# api (default) | fixture — datos de ejemplo sin backend
LANDING_DATA_SOURCE=api
```

- [ ] **Step 4: Job de CI para Next.js**

En `.github/workflows/ci.yml`, añadir al final de `jobs`:

```yaml
  frontend-next:
    name: Frontend Next.js (lint, test, build)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: frontend
    steps:
      - uses: actions/checkout@v4

      - uses: pnpm/action-setup@v4
        with:
          version: 10.15.0

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'pnpm'
          cache-dependency-path: frontend/pnpm-lock.yaml

      - name: Install dependencies
        run: pnpm install --frozen-lockfile

      - name: Lint
        run: pnpm lint

      - name: Unit tests
        run: pnpm test

      - name: Build
        run: pnpm build
        env:
          NEXT_PUBLIC_APP_URL: https://facturon.ec
```

- [ ] **Step 5: Docs**

En `docs/DEPLOYMENT.md`, añadir una sección "Frontend (Next.js) y landing":

```markdown
## Frontend (Next.js) y landing

La landing pública (`/`) y el panel viven en el contenedor `frontend`. `deploy.sh update`
solo reconstruye Laravel; para publicar cambios del frontend:

    cd /opt/factura-ec-saas && git pull --ff-only
    docker compose -f docker/docker-compose.production.yml build frontend
    docker compose -f docker/docker-compose.production.yml up -d frontend
    docker compose -f docker/docker-compose.production.yml up -d --force-recreate nginx

Variables de la landing (en `backend/.env`, leídas por compose como build args):
`LANDING_WHATSAPP`, `LANDING_CONTACT_EMAIL`, `STORE_PLAY_URL`, `STORE_APPSTORE_URL`.
Cambiarlas requiere reconstruir la imagen del frontend. Los planes y los textos de la
sección de precios se editan en Filament y se reflejan en ≤ 5 minutos sin redeploy.

Verificación tras desplegar: `curl -sI https://<dominio>/` (200, HTML de la landing),
`/robots.txt`, `/sitemap.xml`, `/llms.txt`, `/api/v1/public/landing` (200 JSON) y
login → `/dashboard`.
```

- [ ] **Step 6: Verificar y commit**

```bash
cd frontend && pnpm lint && pnpm typecheck && pnpm test && pnpm build
cd ../backend && php artisan test
```

Expected: todo PASS.

```bash
git add docker/nginx/conf.d/production.conf backend/routes/web.php backend/resources/views/welcome.blade.php backend/public/robots.txt frontend/Dockerfile docker/docker-compose.production.yml backend/.env.production frontend/.gitignore frontend/.env.example .github/workflows/ci.yml docs/DEPLOYMENT.md
git commit -m "chore(landing): nginx sin switch de raíz, retiro de la landing Blade, build args, CI y docs"
```

---

### Task 14: Despliegue y verificación en producción

Se ejecuta en el servidor que esté sirviendo producción en ese momento. Hoy: **`159.89.40.217`** (nuevo, acceso `ssh root@159.89.40.217` con la llave por defecto `~/.ssh/id_rsa`) una vez aprovisionado y con los datos migrados; si la migración aún no está hecha, usar `ssh -i ~/.ssh/facturaec_do root@157.230.236.198`.

- [ ] **Step 1: Push y despliegue**

```bash
git push origin main
ssh root@159.89.40.217 'cd /opt/factura-ec-saas && git pull --ff-only origin main && ./deploy.sh update'
ssh root@159.89.40.217 'cd /opt/factura-ec-saas && docker compose -f docker/docker-compose.production.yml build frontend && docker compose -f docker/docker-compose.production.yml up -d frontend && docker compose -f docker/docker-compose.production.yml up -d --force-recreate nginx'
```

Expected: `deploy.sh update` termina con `ACTUALIZACION EXITOSA`; el build del frontend termina sin errores; `docker compose ps` muestra `frontend` y `nginx` `Up`.

- [ ] **Step 2: Verificación externa**

```bash
DOMAIN=facturacion.amephia.com
curl -s -o /dev/null -w "landing %{http_code}\n" https://$DOMAIN/
curl -s https://$DOMAIN/ | grep -o '<h1[^>]*>[^<]*' | head -1
curl -s -o /dev/null -w "robots %{http_code}\n" https://$DOMAIN/robots.txt
curl -s -o /dev/null -w "sitemap %{http_code}\n" https://$DOMAIN/sitemap.xml
curl -s https://$DOMAIN/llms.txt | head -3
curl -s https://$DOMAIN/api/v1/public/landing | head -c 300; echo
curl -s -o /dev/null -w "dashboard sin sesión %{http_code} -> %{redirect_url}\n" https://$DOMAIN/dashboard
curl -s -o /dev/null -w "terms %{http_code}\n" https://$DOMAIN/terms
```

Expected: `landing 200` con el h1 de la landing; robots/sitemap 200; llms.txt empieza con `# Facturón`; la API devuelve JSON con `"plans"`; `/dashboard` 307 hacia `/api/auth/clear?next=/login…`; `/terms` 200.

- [ ] **Step 3: Revisión visual y del flujo de sesión**

Abrir `https://$DOMAIN/` en móvil y escritorio: hero con la demo corriendo, capturas cargadas, precios reales, WhatsApp flotante. Iniciar sesión → llega a `/dashboard`; volver a `/` → la nav muestra "Ir a mi panel".

- [ ] **Step 4: Rich Results Test**

Pegar `https://$DOMAIN/` en https://search.google.com/test/rich-results → `FAQ` y `Organization`/`SoftwareApplication` detectados sin errores.

---

## Self-review del plan

- **Cobertura del spec:** §4 estructura → Tasks 7–11; §5 demo → Task 5 y 8; §6 visual → Tasks 7–10 (tokens, fuentes, `next/image`, reduced motion); §7 arquitectura → Tasks 2, 6, 7, 11; §8 endpoint → Task 1; §9 SEO/GEO → Task 11 (+ e2e Task 12); §10 nginx/Laravel → Task 13; §11 configuración → Tasks 4 y 13; §12 pruebas → cada task + Task 12; §13 rendimiento → Task 12 (Lighthouse); §14 despliegue → Task 14. El fix de los formularios de auth (pedido después del spec) → Task 3.
- **Sin placeholders:** cada paso de código incluye el código completo; los pasos condicionales (grep en Task 2, tenant demo en Task 9) dicen exactamente qué hacer en cada rama.
- **Consistencia de tipos:** `LandingPlan`/`LandingData`/`PricingContent` (Task 4) se usan en Tasks 6, 8, 10, 11; `IconName` (Task 4) en `icons.ts` (Task 7) y secciones (Tasks 9–10); `DemoStep`/`isAtLeast`/`stepIndex` (Task 5) en `live-demo.tsx` (Task 8); `getSession` existente en `nav.tsx`; `whatsappUrl`/`formatWhatsapp`/`CONTACT_EMAIL` (Task 4) en footer, FAQ, CTA, árbitros, llms.
