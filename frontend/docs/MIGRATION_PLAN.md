# Plan de migración Livewire → Next.js 16

## Estrategia

**Incremental, no big-bang.** El panel Livewire del backend sigue vivo y
funcional durante toda la migración. Reemplazamos módulo por módulo.

Cada módulo se considera "migrado" cuando:

1. Existen las pantallas equivalentes en Next.js bajo `src/app/(panel)/<módulo>/`.
2. Cubren los flujos críticos (lista, alta, edición, acciones específicas).
3. Hay tests E2E mínimos (Playwright) para los flujos críticos.
4. Se valida en staging contra datos reales.
5. Se redirige el dominio o ruta de ese módulo del panel Laravel al panel Next.js.

Hasta entonces, ambos panels coexisten — los usuarios pueden seguir usando
Livewire mientras se migra.

## Fases

### Fase 0 — Fundación ✅ (esta sesión)

- [x] Scaffold Next.js 16 + Tailwind v4 + shadcn/ui
- [x] BFF auth (httpOnly cookie + Server Actions + proxy route)
- [x] Layout panel (sidebar + topbar + user menu + theme toggle)
- [x] Login + Register + Forgot password
- [x] Dashboard (server-fetched, ejemplo Server Components)
- [x] Documents list (client-side con React Query, ejemplo CRUD)
- [x] `proxy.ts` (auth gate, antes llamado `middleware.ts`)
- [x] Build + lint limpios

### Fase 1 — Comercial básico (4-6 semanas)

Módulos de mayor uso diario, alto ROI.

- [ ] **Documents — alta + edición + acciones** (firmar, enviar SRI, descargar RIDE/XML, anular, reenviar email)
  - Backend: `app/Livewire/Panel/Documents/`
  - Frontend: `src/app/(panel)/documents/{new,[id]}/`
- [ ] **Customers** (CRUD + búsqueda + ver documentos del cliente)
- [ ] **Products** (CRUD + ajuste de stock + categorías)
- [ ] **Categories** (árbol jerárquico)
- [ ] **Suppliers** (CRUD)

**Entregables:** flujo completo "crear factura → firmar → enviar SRI" en Next.js, paridad con Livewire.

### Fase 2 — POS + Inventario (3-4 semanas)

- [ ] **POS** (sesiones, transacciones, cierre de caja, devoluciones, recibo)
  - Diseño touch-friendly, atajos teclado, búsqueda rápida productos.
- [ ] **Inventory** (kardex, movimientos, alertas low-stock, transferencias)
- [ ] **Recurring Invoices**

### Fase 3 — Compras (2-3 semanas)

- [ ] **Purchases**
- [ ] **Received Documents** (compras electrónicas SRI)
- [ ] **Personal Expenses**

### Fase 4 — Reportes (2 semanas)

- [ ] **Reports** (ventas, IVA, ATS, retenciones, comparativos)
- [ ] Exportación CSV / Excel / PDF
- [ ] Gráficos con Recharts o Tremor

### Fase 5 — Contabilidad (4-6 semanas)

El módulo más grande. 20 componentes Livewire en backend, varios servicios.

- [ ] **Accounts** (plan de cuentas, jerarquía)
- [ ] **Journal Entries** (asientos, posteo, anulación)
- [ ] **Cost Centers**
- [ ] **Budgets** (aprobación, activación, cierre)
- [ ] **Fiscal Periods** (apertura/cierre/bloqueo/reapertura)
- [ ] **Reports**: trial-balance, balance-sheet, income-statement, general-ledger, cash-flow
- [ ] **Tax Forms** (formularios tributarios)

### Fase 6 — Configuración + soporte (2 semanas)

- [ ] **Settings** (perfil, empresa, sucursales, puntos de emisión, certificado, plantillas, plan, suscripción, API keys, activity log)
- [ ] **Support** (tickets, prioridad, asignación, SLA)
- [ ] **Quotes** (cotizaciones + conversión a factura)
- [ ] **Guides** (guías de remisión)

### Fase 7 — Onboarding + landing (1-2 semanas)

- [ ] **Onboarding wizard**
- [ ] **Landing page** pública (SEO, marketing)
- [ ] Integración referidos

### Fase 8 — Apagar Livewire (1 semana)

- [ ] Verificar paridad funcional en todos los módulos
- [ ] Mover redirects: `/panel/*` → Next.js
- [ ] Eliminar `backend/app/Livewire/Panel/` y vistas asociadas
- [ ] Mantener Filament admin (super-admin) intacto
- [ ] Backend Laravel queda como API-only

## Esfuerzo estimado total

**5-7 meses** con 1 dev full-time, **3-4 meses** con 2 devs.

## Reglas durante la migración

1. **No tocar Filament**: el super-admin queda en Filament. Reemplazarlo es 2 meses extra a cambio de cero valor.
2. **No tocar Mobile**: Flutter sigue contra la misma API v1 que Next.js.
3. **API contract first**: si un módulo necesita endpoint nuevo o cambio, primero lo agregamos al backend con tests, luego lo consumimos del frontend.
4. **Cada módulo debe tener tests** antes de apagar su versión Livewire.
5. **Coexistencia con redirects**: durante una fase, parte del panel apunta a Livewire (Laravel) y parte a Next.js. El usuario no debe notar.

## Riesgos

- **Wizard contable** (apertura período, cierre fiscal): lógica compleja con efectos en BD. Migrar con cuidado, mantener Livewire como fallback hasta validar.
- **POS offline**: Livewire actual no es offline tampoco, pero Next.js debería evaluar Service Worker + IndexedDB en Fase 2.
- **Generación PDF (RIDE)** y **firma XAdES**: ambas se hacen server-side en Laravel. Frontend solo descarga. NO mover firma al cliente.
- **2FA Fortify**: actualmente solo en panel web. Para Next.js hay que adaptar (Sanctum no expone 2FA challenge directamente). Ver Fase 0+ una vez auth básica esté en producción.

## Quick wins paralelos a la migración

Mientras avanza la migración, el equipo puede:

- Mejorar API REST (ej: agregar filtros faltantes que el panel Livewire hacía con queries directas).
- Agregar Scramble docs por endpoint a medida que se consumen.
- Aumentar cobertura de tests del backend (es lo que protege la migración).
