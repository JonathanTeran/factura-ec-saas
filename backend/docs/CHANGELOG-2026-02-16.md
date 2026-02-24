# Changelog - 16 Feb 2026

## Resumen

Se completaron 3 bloques de trabajo:

1. **Correccion de 17 archivos** con referencia rota al modelo fantasma `App\Models\Documents\Document`
2. **Implementacion completa de Webhooks** (feature del plan)
3. **Implementacion completa de Facturas Recurrentes** (feature del plan)

---

## 1. Correccion de Referencias Rotas: Document -> ElectronicDocument

### Problema

17 archivos referenciaban `App\Models\Documents\Document`, un modelo que nunca fue creado. El unico modelo de documentos en el sistema es `App\Models\SRI\ElectronicDocument`. Ademas de los imports, habia incompatibilidades de propiedades entre lo que el codigo esperaba y lo que el modelo real ofrece.

### Mapeo de propiedades corregido

| Propiedad fantasma         | Propiedad real en ElectronicDocument           |
|----------------------------|------------------------------------------------|
| `pdf_path`                 | `ride_pdf_path`                                |
| `xml_path`                 | `xml_authorized_path` / `xml_signed_path`      |
| `tax_amount`               | `total_tax`                                    |
| `subtotal`                 | `getSubtotal()` (metodo, no propiedad)         |
| `emailed_at`               | `email_sent_at` + `email_sent`                 |
| `document_number`          | `getDocumentNumber()` (se agrego accessor)     |
| `documentType->name`       | `document_type->label()` (enum cast)           |
| `$doc->electronicDocument` | eliminado (auto-referencia innecesaria)         |

### Accessor agregado al modelo

```php
// app/Models/SRI/ElectronicDocument.php
public function getDocumentNumberAttribute(): string
{
    return $this->getDocumentNumber();
}
```

### Archivos corregidos (17)

**Jobs (5):**
- `app/Jobs/CheckDocumentAuthorizationJob.php` - Import, type hint, eliminada auto-referencia `$this->document->electronicDocument`
- `app/Jobs/SendDocumentToSriJob.php` - Import, type hint, eliminada auto-referencia
- `app/Jobs/SignDocumentJob.php` - Import, type hint, `xml_content`/`signed_at` -> `xml_signed_path`
- `app/Jobs/GenerateDocumentPdfJob.php` - Import, type hint, `pdf_path` -> `ride_pdf_path`
- `app/Jobs/SendDocumentEmailJob.php` - Import, type hint, `emailed_at` -> `email_sent`/`email_sent_at`

**Events (3):**
- `app/Events/DocumentVoided.php` - Import y type hint
- `app/Events/DocumentSigned.php` - Import y type hint
- `app/Events/DocumentCreated.php` - Import y type hint

**Listeners (2):**
- `app/Listeners/SendDocumentAuthorizedNotification.php` - Simplificado: usa `$event->document` directamente en vez de query redundante
- `app/Listeners/SendDocumentRejectedNotification.php` - Simplificado: usa `$event->document` directamente, `sri_errors` accedido directo

**Notifications (3):**
- `app/Notifications/DocumentEmailNotification.php` - `documentType->name` -> `document_type->label()`, `subtotal` -> `getSubtotal()`, `tax_amount` -> `total_tax`, `pdf_path` -> `ride_pdf_path`, `xml_path` -> `xml_authorized_path ?? xml_signed_path`
- `app/Notifications/DocumentRejectedNotification.php` - `documentType->name` -> `document_type->label()`
- `app/Notifications/DocumentAuthorizedNotification.php` - `documentType->name` -> `document_type->label()`

**Otros (4):**
- `app/Providers/AuthServiceProvider.php` - `Document::class` -> `ElectronicDocument::class` en policy map
- `app/Policies/DocumentPolicy.php` - Todos los type hints de metodos cambiados
- `app/Livewire/Panel/Documents/DocumentShow.php` - Propiedad y mount signature
- `routes/console.php` - Query y fix de status invalido `'pending'` -> `'processing'`

---

## 2. Webhooks

Sistema completo para enviar notificaciones HTTP a endpoints externos cuando ocurren eventos en documentos electronicos.

### Arquitectura

```
Evento (DocumentAuthorized, etc.)
  -> DispatchWebhookListener (event subscriber)
    -> Verifica tenant.has_webhooks
    -> WebhookService::dispatchForDocument()
      -> Busca WebhookEndpoints activos suscritos al evento
      -> Despacha SendWebhookJob por cada endpoint
        -> POST con payload JSON
        -> Firma HMAC-SHA256: hash_hmac('sha256', "{timestamp}.{body}", secret)
        -> Headers: X-Webhook-Event, X-Webhook-Timestamp, X-Webhook-Signature
        -> 5 reintentos con backoff exponencial [10, 30, 120, 300, 900]s
        -> Auto-desactiva endpoint tras 10 fallos consecutivos
```

### Eventos soportados

| Evento                  | Label en UI               |
|-------------------------|---------------------------|
| `document.authorized`   | Documento Autorizado      |
| `document.rejected`     | Documento Rechazado       |
| `document.created`      | Documento Creado          |
| `document.signed`       | Documento Firmado         |
| `document.voided`       | Documento Anulado         |
| `document.failed`       | Documento Fallido         |

### Payload del webhook

```json
{
  "event": "document.authorized",
  "timestamp": "2026-02-16T10:00:00Z",
  "data": {
    "id": 123,
    "document_type": "FACTURA",
    "document_number": "001-001-000000001",
    "access_key": "...",
    "status": "AUTHORIZED",
    "authorization_number": "...",
    "authorization_date": "...",
    "issue_date": "...",
    "subtotal": 100.00,
    "total_tax": 15.00,
    "total": 115.00,
    "customer": { "name": "...", "identification": "..." },
    "company": { "business_name": "...", "ruc": "..." },
    "sri_errors": null
  }
}
```

### Verificacion de firma (para el receptor)

```php
$timestamp = $request->header('X-Webhook-Timestamp');
$signature = $request->header('X-Webhook-Signature');
$body = $request->getContent();

$expected = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
$valid = hash_equals($expected, $signature);
```

### Archivos creados/modificados

| Archivo | Accion |
|---------|--------|
| `app/Models/Tenant/WebhookEndpoint.php` | Creado - Modelo con BelongsToTenant |
| `app/Jobs/SendWebhookJob.php` | Creado - Job con reintentos y firma HMAC |
| `app/Services/WebhookService.php` | Creado - Logica de despacho |
| `app/Listeners/DispatchWebhookListener.php` | Creado - Event subscriber |
| `app/Livewire/Panel/Settings/WebhookSettings.php` | Creado - CRUD de endpoints |
| `resources/views/livewire/panel/settings/webhook-settings.blade.php` | Creado - UI completa |
| `database/migrations/2026_02_17_000001_add_has_webhooks_to_tenants_table.php` | Creado |
| `app/Models/Tenant/Tenant.php` | Modificado - fillable, casts, hasFeature, syncPlanLimits, relacion |
| `app/Providers/EventServiceProvider.php` | Modificado - registrado subscriber |
| `routes/web.php` | Modificado - ruta settings/webhooks |
| `app/Livewire/Panel/Settings/SettingsIndex.php` | Modificado - seccion webhooks en nav |

### Acceso

- Ruta: `/panel/settings/webhooks`
- Requiere: Plan con `has_webhooks = true`
- UI: CRUD completo con toggle activo/inactivo, regenerar secret, reset de fallos

---

## 3. Facturas Recurrentes

Sistema para programar emision automatica de facturas con frecuencia configurable.

### Arquitectura

```
RecurringInvoice (template)
  -> ProcessRecurringInvoicesJob (schedule diario 6 AM)
    -> RecurringInvoiceService::processAllDue()
      -> Para cada recurrente isDue():
        -> Verifica tenant activo, feature habilitado, limite de documentos
        -> generateDocument(): crea ElectronicDocument con items
        -> advanceToNextIssue(): calcula siguiente fecha, incrementa contador
        -> Si max_issues alcanzado: status -> 'completed'
```

### Frecuencias

| Valor       | Label      | Intervalo          |
|-------------|------------|---------------------|
| `weekly`    | Semanal    | +1 semana           |
| `biweekly`  | Quincenal  | +2 semanas          |
| `monthly`   | Mensual    | +1 mes              |
| `bimonthly` | Bimestral  | +2 meses            |
| `quarterly` | Trimestral | +3 meses            |
| `semiannual`| Semestral  | +6 meses            |
| `annual`    | Anual      | +1 anio             |

### Tabla: `recurring_invoices`

Columnas principales:
- `tenant_id`, `company_id`, `branch_id`, `emission_point_id`, `customer_id`, `created_by`
- `frequency` (enum), `start_date`, `end_date`, `next_issue_date`
- `status` (active, paused, completed, cancelled)
- `items` (JSON) - array de items con description, quantity, unit_price, tax_rate, aux_code
- `payment_methods`, `additional_info` (JSON)
- `notes`, `currency`
- `total_issued`, `max_issues`, `last_issued_at`
- `notify_before_issue`, `notify_days_before`

Relacion en `electronic_documents`: FK `recurring_invoice_id` para rastrear documentos generados.

### Items JSON structure

```json
[
  {
    "description": "Servicio de hosting mensual",
    "quantity": 1,
    "unit_price": 50.00,
    "tax_rate": 15,
    "aux_code": "SRV001"
  }
]
```

### Archivos creados/modificados

| Archivo | Accion |
|---------|--------|
| `database/migrations/2026_02_17_000002_create_recurring_invoices_table.php` | Creado |
| `app/Models/Tenant/RecurringInvoice.php` | Creado - Modelo completo con scopes y helpers |
| `app/Services/RecurringInvoiceService.php` | Creado - processAllDue(), generateDocument() |
| `app/Jobs/ProcessRecurringInvoicesJob.php` | Creado - Job scheduleable |
| `app/Livewire/Panel/RecurringInvoices/RecurringInvoiceList.php` | Creado - Lista con filtros |
| `resources/views/livewire/panel/recurring-invoices/recurring-invoice-list.blade.php` | Creado |
| `app/Livewire/Panel/RecurringInvoices/RecurringInvoiceForm.php` | Creado - Formulario CRUD |
| `resources/views/livewire/panel/recurring-invoices/recurring-invoice-form.blade.php` | Creado |
| `app/Models/SRI/ElectronicDocument.php` | Modificado - relacion `recurringInvoice()` |
| `app/Models/Tenant/Tenant.php` | Modificado - relacion `recurringInvoices()` |
| `routes/web.php` | Modificado - rutas recurring-invoices (index, create, edit) |
| `routes/console.php` | Modificado - schedule diario para ProcessRecurringInvoicesJob |

### Rutas

| Ruta | Componente | Nombre |
|------|-----------|--------|
| `GET /panel/recurring-invoices` | RecurringInvoiceList | `panel.recurring-invoices.index` |
| `GET /panel/recurring-invoices/create` | RecurringInvoiceForm | `panel.recurring-invoices.create` |
| `GET /panel/recurring-invoices/{id}/edit` | RecurringInvoiceForm | `panel.recurring-invoices.edit` |

### Acceso

- Requiere: Plan con `has_recurring_invoices = true`
- Si no tiene feature: muestra banner de upgrade
- Acciones: crear, editar, pausar/activar, cancelar, eliminar

### Schedule

```php
// routes/console.php
Schedule::job(new ProcessRecurringInvoicesJob())
    ->dailyAt('06:00')
    ->name('process-recurring-invoices')
    ->withoutOverlapping()
    ->onOneServer();
```

---

## Migraciones pendientes de ejecutar

```bash
php artisan migrate
```

Migraciones nuevas:
1. `2026_02_17_000001_add_has_webhooks_to_tenants_table.php`
2. `2026_02_17_000002_create_recurring_invoices_table.php`

---

## 4. Exception Handling Custom

Sistema de excepciones tipadas para reemplazar `throw new \Exception()` generico en SRI, pagos, certificados y limites de plan.

### Jerarquia de excepciones

```
SriException
  ├── SriCommunicationException    (red/timeout con SRI)
  └── SriRejectionException        (documento rechazado, con array de errores)

CertificateException
  ├── CertificateNotFoundException (empresa sin certificado)
  ├── CertificateExpiredException  (certificado expirado, con fecha)
  ├── InvalidCertificateException  (contrasena incorrecta, formato invalido)
  └── SignatureException           (error al firmar XML)

PaymentException
  ├── PaymentFailedException       (tarjeta rechazada, fondos insuficientes)
  ├── PaymentGatewayException      (error API de Stripe/PayPal/Paymentez)
  └── RefundFailedException        (reembolso fallido)

PlanLimitExceededException         (documentos/usuarios/empresas)
FeatureNotAvailableException       (webhooks, recurrentes, API, etc.)
TenantInactiveException            (cuenta suspendida/cancelada)
DocumentProcessingException        (error generico de procesamiento)
```

### Excepciones con contexto

Todas implementan `context(): array` que retorna metadata util para logging:

```php
// Ejemplo: SriRejectionException
$e->context();
// ['document_id' => 123, 'access_key' => '...', 'sri_errors' => [...]]

// Ejemplo: PlanLimitExceededException
$e->context();
// ['resource' => 'documents', 'limit' => 500, 'used' => 500]
```

### Respuestas HTTP automaticas (bootstrap/app.php)

| Excepcion | API (JSON) | Web |
|-----------|-----------|-----|
| `SriRejectionException` | 422 `sri_rejection` | default |
| `SriCommunicationException` | 503 `sri_unavailable` | default |
| `CertificateNotFoundException` | 422 `certificate_not_found` | default |
| `CertificateExpiredException` | 422 `certificate_expired` | default |
| `PaymentFailedException` | 402 `payment_failed` | default |
| `PaymentGatewayException` | 502 `payment_gateway_error` | default |
| `PlanLimitExceededException` | 403 `plan_limit_exceeded` | redirect billing |
| `FeatureNotAvailableException` | 403 `feature_not_available` | redirect billing |
| `TenantInactiveException` | 403 `tenant_inactive` | default |

### Archivos creados (13 excepciones)

| Archivo |
|---------|
| `app/Exceptions/SriException.php` |
| `app/Exceptions/SriCommunicationException.php` |
| `app/Exceptions/SriRejectionException.php` |
| `app/Exceptions/CertificateException.php` |
| `app/Exceptions/CertificateNotFoundException.php` |
| `app/Exceptions/CertificateExpiredException.php` |
| `app/Exceptions/InvalidCertificateException.php` |
| `app/Exceptions/SignatureException.php` |
| `app/Exceptions/PaymentException.php` |
| `app/Exceptions/PaymentFailedException.php` |
| `app/Exceptions/PaymentGatewayException.php` |
| `app/Exceptions/RefundFailedException.php` |
| `app/Exceptions/PlanLimitExceededException.php` |
| `app/Exceptions/FeatureNotAvailableException.php` |
| `app/Exceptions/TenantInactiveException.php` |
| `app/Exceptions/DocumentProcessingException.php` |

### Archivos modificados (integracion)

| Archivo | Cambios |
|---------|---------|
| `app/Services/SRI/SignatureManager.php` | `\Exception` -> `CertificateNotFoundException`, `InvalidCertificateException`, `CertificateExpiredException` |
| `app/Services/SRI/SRIService.php` | `\Exception` -> `SriException`, `SriRejectionException`, `DocumentProcessingException` |
| `app/Services/Certificate/CertificateService.php` | `\Exception` -> `CertificateNotFoundException`, `CertificateExpiredException`, `InvalidCertificateException` |
| `app/Services/Billing/PaymentGatewayService.php` | `\Exception` -> `PaymentFailedException`, `PaymentGatewayException` (Stripe, PayPal, Paymentez) |
| `app/Services/Billing/BillingService.php` | catch `PaymentException` especifico + fallback a `PaymentException` generico |
| `app/Jobs/SignDocumentJob.php` | catch `CertificateException` separado + wrap en `SignatureException` |
| `app/Jobs/SendDocumentToSriJob.php` | catch `SriRejectionException` (fail inmediato) vs `SriCommunicationException` (retry) |
| `app/Jobs/CheckDocumentAuthorizationJob.php` | catch `SriCommunicationException` para retry |
| `app/Http/Middleware/CheckPlanLimits.php` | throw `PlanLimitExceededException` en vez de response manual |
| `bootstrap/app.php` | Renderers para todas las excepciones custom (JSON + web) |

---

## Pendiente (no implementado aun)

- **POS (Punto de Venta)**: Feature flag existe (`has_pos`) pero sin implementacion
- **AI Categorization**: Feature flag existe pero sin implementacion
