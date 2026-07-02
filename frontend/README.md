# Frontend — AmePhia Facturación

Next.js 16 + App Router + React 19 + Tailwind v4 + shadcn/ui. Reemplazo
incremental del panel Livewire del backend Laravel.

## Stack

- **Next.js 16** (App Router, Turbopack, Server Components, Server Actions)
- **React 19**
- **TypeScript**
- **Tailwind v4** + **shadcn/ui** (Radix, new-york style, slate base)
- **TanStack Query v5** para data fetching cliente
- **react-hook-form** + **zod** para formularios
- **next-themes** + **sonner** para tema y notificaciones
- **pnpm**

## Arquitectura de auth (BFF)

El backend Laravel emite **Bearer tokens** (Sanctum). El frontend NO los expone
al navegador. En su lugar:

1. Server Actions (`src/app/(auth)/actions.ts`) llaman al backend con
   credenciales y reciben el token.
2. El token se guarda en una cookie **httpOnly + SameSite=Lax** (`factura_session`).
3. Los Server Components leen la cookie con `cookies()` y llaman al backend
   con `Authorization: Bearer ...` vía `src/lib/server/api.ts`.
4. Los Client Components hacen requests a `/api/proxy/<path>` (mismo origen,
   sin CORS) — el route handler en `src/app/api/proxy/[...path]/route.ts`
   inyecta el token desde la cookie y forwardea a Laravel.

Así el token nunca es accesible desde JavaScript del navegador (XSS-safe) y
no necesitamos CORS con `withCredentials` en el cliente.

## Estructura

```
src/
├─ app/
│  ├─ (auth)/                 # /login, /register, /forgot-password
│  │  └─ actions.ts           # Server Actions: login, register, logout
│  ├─ (panel)/                # rutas protegidas, layout con sidebar
│  │  ├─ layout.tsx           # requireUser() + Sidebar + Topbar
│  │  ├─ page.tsx             # Dashboard (server-fetched)
│  │  └─ documents/           # ejemplo client-side con React Query
│  ├─ api/proxy/[...path]/    # BFF proxy a Laravel /api/v1/*
│  └─ layout.tsx              # root layout con Providers
├─ components/
│  ├─ panel/                  # sidebar, topbar, user-menu, page-header
│  ├─ providers.tsx           # QueryClient + ThemeProvider + Toaster
│  └─ ui/                     # shadcn primitives
├─ lib/
│  ├─ api/                    # client.ts (browser), types.ts, queries/
│  ├─ auth/session.ts         # cookie helpers
│  ├─ server/api.ts           # server-side fetch helper
│  └─ server/auth.ts          # getCurrentUser, requireUser
└─ proxy.ts                   # auth gate (formerly middleware.ts in v15)
```

## Setup local

```bash
cp .env.example .env.local
# Edita LARAVEL_API_URL si tu backend no está en localhost:8000
pnpm install
pnpm dev
```

Abre <http://localhost:3000>. Te redirige a `/login` si no tienes sesión.

Para que login funcione contra el backend Laravel:

1. Backend corriendo en `http://localhost:8000`
2. CORS ya soporta `localhost:3000` (ver `backend/config/cors.php`)

## Comandos

```bash
pnpm dev          # dev server con Turbopack
pnpm build        # build producción
pnpm start        # serve build
pnpm lint         # ESLint
```

## Migración desde Livewire — ver `docs/MIGRATION_PLAN.md`

El backend Laravel sigue funcionando en paralelo. Cada módulo Livewire se
reemplaza módulo por módulo siguiendo el orden definido en el plan.
