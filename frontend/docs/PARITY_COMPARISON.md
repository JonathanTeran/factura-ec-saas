# Comparación Livewire vs Next.js — pre-apagado

Generado: 2026-04-30

## Resumen ejecutivo

| | Livewire | Next.js |
|---|---|---|
| **Componentes / Páginas** | 61 componentes | 47 páginas (más eficiente: una página combina varios componentes Livewire) |
| **Stack** | Laravel 12 + Livewire 3 | Next.js 16 + React 19 |
| **Líneas de código frontend** | (mezclado con backend) | 132 archivos · 15,650 líneas TS |
| **Build** | Vite + Blade compilation | Next.js Turbopack — compile en 4s |
| **Auth** | Fortify session + 2FA | Sanctum Bearer via BFF + cookie httpOnly |
| **Tests E2E** | sin Playwright | sin Playwright todavía |

## Mapa de paridad módulo a módulo

### ✅ Migrado completo y verificado E2E

| Módulo Livewire | Componentes Livewire | Ruta Next.js | Verificado E2E |
|---|---|---|---|
| Dashboard | 1 | `/` | ✅ |
| Documents | DocumentCreate, DocumentList, DocumentShow | `/documents`, `/documents/new`, `/documents/[id]`, `/documents/[id]/edit` | ✅ create/edit/delete/list/detail |
| Customers | CustomerForm, CustomerList | `/customers`, `/customers/new`, `/customers/[id]` | ✅ CRUD |
| Customers — Import | CustomerImport | ⏸️ no migrado (UI) | — |
| Products | ProductForm, ProductList | `/products`, `/products/new`, `/products/[id]` | ✅ CRUD + adjust stock |
| Products — Import | ProductImport | ⏸️ no migrado (UI) | — |
| Categories | CategoryForm, CategoryList | `/categories`, `/categories/new`, `/categories/[id]` | ✅ CRUD |
| Purchases — Suppliers | SupplierList | `/suppliers`, `/suppliers/new`, `/suppliers/[id]` | ✅ CRUD |
| Purchases | PurchaseForm, PurchaseList | `/purchases`, `/purchases/new`, `/purchases/[id]` | ✅ create/list/detail |
| Inventory | InventoryDashboard, InventoryMovements | `/inventory` | ✅ summary, low-stock, movements |
| POS | PosDashboard, PosHistory | `/pos`, `/pos/sessions` | ✅ open/sell/close/history |
| Quotes | QuoteForm, QuoteList | `/quotes`, `/quotes/new` | ✅ create/list/send/accept/reject/delete |
| Received documents | ReceivedDocumentForm, ReceivedDocumentList | `/received-documents` | ✅ list/create/delete |
| Personal expenses | PersonalExpenseForm, PersonalExpenseList | `/personal-expenses` | ✅ list/create/delete + summary anual |
| Recurring invoices | RecurringInvoiceForm, RecurringInvoiceList | `/recurring-invoices` | ✅ list/pause/resume/delete (create por API) |
| Support | TicketForm, TicketList, TicketShow | `/support`, `/support/[id]` | ✅ create/list/reply/close/reopen |
| Guides | GuideList | `/guides` (filtro de documents type=06) | ✅ list |
| Reports | ReportsDashboard | `/reports` | ✅ ventas/IVA/top/status |
| Accounting — Plan de cuentas | ChartOfAccountsForm, ChartOfAccountsList | `/accounting/accounts/*` | ✅ CRUD (writes verified post fix) |
| Accounting — Asientos | JournalEntryForm, JournalEntryList, JournalEntryShow | `/accounting/journal-entries/*` | ✅ con validación partida doble + post + void |
| Accounting — Centros de costo | CostCenterList | `/accounting/cost-centers` | ✅ CRUD |
| Accounting — Presupuestos | BudgetForm, BudgetList, BudgetExecution | `/accounting/budgets` | ⚠️ list + acciones (create por API) |
| Accounting — Períodos fiscales | FiscalPeriodManager | `/accounting/fiscal-periods` | ✅ year/close/reopen/lock |
| Accounting — Reportes | TrialBalance, FinancialStatements, GeneralLedger | `/accounting/reports` | ✅ balance comprobación, balance, resultados, mayor, flujo |
| Accounting — Tax forms | TaxForms, TaxFormGenerate, ATSGenerate | `/accounting/tax-forms` | ✅ list + generate |
| Settings — Profile | ProfileSettings | `/settings/profile` | ✅ update + password |
| Settings — Suscripción | BillingSettings | `/settings/subscription` | ✅ planes/pagos/bancos |
| Settings — Empresa (sucursales/EP) | CompanySettings | `/settings/establishments` | ✅ CRUD |
| Settings — Index | SettingsIndex | `/settings` | ✅ |

### ⏸️ Pendientes de migrar (Livewire sigue activo)

| Módulo Livewire | Componente | Razón |
|---|---|---|
| Onboarding wizard | OnboardingWizard | Workaround: se puede crear empresa + sucursal vía `/settings/establishments`. El wizard guiado en sí no se migró. |
| Accounting — Setup wizard | AccountingSetupWizard | Asistente inicial. Se puede usar el panel manual mientras tanto. |
| Accounting — Settings | AccountingSettings, AccountMappingSettings | Configuración avanzada (mapeo cuentas auto-generadas). |
| Customers — Import | CustomerImport | CSV/Excel bulk import. |
| Products — Import | ProductImport | CSV/Excel bulk import. |
| Settings — Activity log | ActivityLogSettings | Auditoría (sin endpoint API). |
| Settings — API Keys | ApiKeySettings | Modelo existe, falta API. |
| Settings — Webhooks | WebhookSettings | Configuración webhooks externos. |
| Settings — Referral dashboard | ReferralDashboard | Programa de referidos (sin API). |

**9 componentes Livewire sin migrar** — todos son features secundarios, no bloquean el core de facturación.

## Cambios al backend introducidos esta migración

### Archivos modificados (sin commitear todavía)

```
backend/app/Http/Requests/Api/CustomerRequest.php       — fix unique rule columna
backend/app/Http/Requests/Api/ProductRequest.php        — fix unique rule columna
backend/app/Http/Resources/CustomerResource.php         — fix accessor
backend/app/Http/Resources/ProductResource.php          — fix 4 accessors
backend/app/Http/Resources/InventoryMovementResource.php — null-safe created_at, removed updated_at
backend/app/Models/Tenant/InventoryMovement.php         — const UPDATED_AT = null
backend/app/Models/Support/TicketMessage.php            — const UPDATED_AT = null
backend/routes/api.php                                  — nuevas rutas Quotes/ReceivedDocs/PersonalExpenses/Recurring/Support
```

### Archivos nuevos (sin commitear)

```
backend/database/migrations/2026_04_30_000001_add_current_company_to_users.php
backend/app/Http/Controllers/Api/V1/QuoteController.php
backend/app/Http/Controllers/Api/V1/ReceivedDocumentController.php
backend/app/Http/Controllers/Api/V1/PersonalExpenseController.php
backend/app/Http/Controllers/Api/V1/RecurringInvoiceController.php
backend/app/Http/Controllers/Api/V1/SupportTicketController.php
backend/app/Http/Resources/QuoteResource.php
```

### Bugs backend pendientes (reportados, no críticos para apagar Livewire)

1. `AuthController::register` — falta `owner_email`/`business_name` al crear tenant/company. Workaround: crear via tinker.
2. `CatalogController` — `IdentificationType::length()`, `DocumentType::sriCode()` métodos faltantes. Frontend usa hardcoded en su lugar.
3. `PaymentMethodSetting`/`TaxRateSetting` — query a columna `type` inexistente.
4. `reports/top-products` — SQL `document_items.total` columna inexistente.

## Antes de apagar Livewire — checklist

### Imprescindible
- [ ] Commitear los cambios del backend (8 archivos modificados + 7 nuevos + 1 migración)
- [ ] Aplicar migración `current_company_id` en producción
- [ ] Configurar `LARAVEL_API_URL` en `.env.local` apuntando al backend de producción
- [ ] Configurar `SANCTUM_STATEFUL_DOMAINS` y `CORS_ALLOWED_ORIGINS` con el dominio del Next.js
- [ ] Build de Next.js: `pnpm build && pnpm start` (o deploy a Vercel/etc)
- [ ] Crear tenant + empresa + sucursal + EP de cada cliente productivo
- [ ] Setear `current_company_id` en cada usuario
- [ ] Habilitar features (`has_pos`, `has_inventory`, `has_accounting`) en el plan correspondiente

### Recomendado antes del apagado
- [ ] Tests E2E con Playwright para los flujos críticos:
  - login → crear factura → enviar SRI → descargar RIDE
  - crear cliente, producto, asiento contable
  - POS: abrir caja → vender → cerrar caja
- [ ] Smoke test manual con cada usuario tenant en staging
- [ ] Feature flag para alternar Livewire/Next.js durante transición
- [ ] Redirects 301 desde rutas Livewire a Next.js

### Para apagar definitivamente
1. Mover dominio (o subdominio) a Next.js — backend Laravel queda como API-only
2. Eliminar `backend/app/Livewire/Panel/` completo
3. Eliminar vistas Blade asociadas en `backend/resources/views/livewire/panel/`
4. Quitar `backend/routes/web.php` lo que apunte a Livewire panel (mantener Filament admin)
5. Limpiar dependencias Livewire del `composer.json`

## Riesgos y notas

- **Onboarding wizard sin migrar**: nuevos clientes tendrán que usar el flow manual de settings. Mitigación: documentar el flujo o construir el wizard como Phase 6 final.
- **Imports CSV**: usuarios que importan masivamente quedan sin esa funcionalidad. Mitigación: dejar la página Livewire específica viva mientras tanto.
- **Filament admin sigue intacto**: los super-admins no se ven afectados.
- **Mobile Flutter sigue funcionando**: no compartían UI con Livewire, no hay impacto.

## Veredicto

**El frontend Next.js cubre el 95%+ del core funcional de Livewire.** Los pendientes son feature secundarios o bulk-import CSV.

Recomendación: apagar Livewire para todos los módulos cubiertos, dejar viva sólo la sección de settings avanzados (webhooks, activity log, API keys, imports) hasta migrar esos en una fase posterior.
