# PayPal como método de pago de las suscripciones — diseño

Fecha: 2026-09-14 · Estado: implementado en la misma sesión (el usuario pidió "Implementa metodología de pago PayPal"; decisiones tomadas con los supuestos de abajo).

## Objetivo

Que un tenant pague su plan de Facturón con PayPal (saldo PayPal o tarjeta dentro de PayPal) y quede activo al instante, sin comprobante ni aprobación manual. La transferencia bancaria sigue igual.

## Supuestos (revisables)

1. **Qué se cobra:** las suscripciones de Facturón (el tenant le paga a AmePhia). No los cobros de los tenants a sus propios clientes.
2. **Modelo de cobro:** pago único por periodo con la Orders API v2 (intent CAPTURE). Sin cobro recurrente automático (PayPal Subscriptions API): el tenant vuelve a pagar al renovar, igual que con transferencia.
3. **Experiencia:** redirección a PayPal (enlace `payer-action`) y regreso a `/settings/subscription/paypal`. Sin SDK de JS en el panel.
4. **Montos:** los mismos que la transferencia: precio del ciclo − cupón = subtotal; IVA 15 % encima; total en USD. Siempre calculados en el servidor.
5. **Credenciales:** las administra el super admin en Filament (Sistema → PayPal): modo sandbox/live, Client ID, Secret (cifrado en BD), ID del webhook. Sin credenciales PayPal no aparece.
6. **Solo web.** La app móvil no ofrece PayPal (políticas de las tiendas).

## Flujo

1. `GET subscription/checkout-options` → `{ bank_transfer: {enabled}, paypal: {enabled, sandbox}, tax_rate }`.
2. `POST subscription/paypal/orders` `{plan_id, billing_cycle, billing_name, billing_email, billing_identification?, coupon_code?}`:
   - 422 si PayPal no está disponible o hay un pago en revisión (transferencia PENDING o PayPal PROCESSING).
   - Cancela órdenes PayPal PENDING anteriores del tenant.
   - Crea `Payment` PENDING (`payment_method=paypal`, `gateway=paypal`, montos, `metadata` con plan/ciclo/cupón). El cupón NO consume usos todavía.
   - Crea la orden en PayPal (`PayPal-Request-Id` idempotente, `custom_id` = id del pago, item DIGITAL_GOODS con IVA, `NO_SHIPPING`, `PAY_NOW`, `IMMEDIATE_PAYMENT_REQUIRED`, `return_url`/`cancel_url` del panel).
   - Responde `{payment_id, order_id, approve_url}`; el panel redirige.
3. PayPal vuelve a `/settings/subscription/paypal?token=ORDER_ID` → `POST subscription/paypal/orders/{order}/capture`:
   - Busca el pago del tenant por `gateway_payment_id` con bloqueo de fila. Ya COMPLETED → responde éxito (idempotente).
   - Captura (`PayPal-Request-Id`). `ORDER_ALREADY_CAPTURED` → consulta la orden y usa su captura.
   - Captura COMPLETED y monto/moneda coinciden → **activación** (abajo). PENDING → pago PROCESSING ("PayPal está revisando"). Monto distinto → PROCESSING + aviso a admins, sin activar. Rechazo → FAILED + mensaje para reintentar.
4. `cancel_url` → `/settings/subscription?paypal=cancelled&token=…` → `POST …/{order}/cancel` marca el pago CANCELED.
5. **Webhook** `POST /api/v1/webhooks/paypal` (público, firma verificada con `verify-webhook-signature` enviando el cuerpo crudo):
   - `CHECKOUT.ORDER.APPROVED` → captura si el comprador cerró el navegador antes de volver.
   - `PAYMENT.CAPTURE.COMPLETED` → completa pagos PENDING/PROCESSING.
   - `PAYMENT.CAPTURE.PENDING` → PROCESSING. `DENIED`/`DECLINED` → FAILED + aviso.
   - `PAYMENT.CAPTURE.REFUNDED`/`REVERSED` → REFUNDED o PARTIALLY_REFUNDED + aviso a admins (no revoca el plan: decide el admin).

## Activación (una sola vez por pago, en transacción)

- Mismo plan y ciclo que la suscripción vigente → **renovación**: `ends_at` se extiende desde el fin actual (no se pierden días) y queda ACTIVE.
- Otro plan/ciclo o sin suscripción → suscripción nueva ACTIVE desde hoy; la anterior se cancela (igual que la transferencia).
- Pago COMPLETED (`transaction_id`/`gateway_transaction_id` = id de la captura), tenant ACTIVE + `syncPlanLimits`, uso del cupón, comisión de referido, caché del tenant invalidado.
- Correo al dueño (`PaymentApprovedNotification`, texto según método) y aviso a los admins.

## Administración

- Página Filament **Sistema → PayPal**: activar, modo, Client ID, Secret (nunca se devuelve al navegador; vacío = conservar), ID del webhook, URL del webhook; acciones **Probar conexión** y **Registrar webhook** (lo crea o reutiliza el existente con esa URL).
- Fuente de verdad del "activado": la fila `paypal` de `payment_method_settings` (también visible en Métodos de pago).
- Reembolso desde Pagos: para PayPal llama a la API de reembolsos; si PayPal falla NO se marca reembolsado.
- El contador de pendientes del menú sigue contando solo transferencias.

## Piezas

- Backend: `PaymentMethod::PAYPAL`; `Services/Settings/PayPalSettings`; `Services/Payment/PayPal/{PayPalClient,PayPalException,PayPalCheckoutService}`; `Services/Billing/{SubscriptionQuote,SubscriptionActivator}`; `PayPalCheckoutController`, `PayPalWebhookController`; migración de la fila `paypal`; ajustes en `SubscriptionController::current`, `PaymentService::processRefund`, `PaymentResource`, `PaymentApprovedNotification`, `AdminEventNotification`; `payment_methods` en la landing pública.
- Panel: selector de método en el diálogo de suscripción, página de regreso, avisos de pago en revisión según método, etiquetas en el historial, franja neutral.
- Landing: nota de precios y FAQ mencionan PayPal solo si está activo.

## Pruebas

Http::fake para PayPal: crear orden (montos, cupón, bloqueos), captura (nueva, renovación, cambio de plan, PENDING, monto distinto, ya capturada, rechazada, de otro tenant), cancelación, webhook (firma inválida, aprobada, completada, reembolsada), reembolso desde el admin, ajustes cifrados y página Filament. Vitest: opción PayPal en el diálogo y estados de la página de regreso.

## Fuera de alcance

Cobro recurrente automático, botones in-context del SDK de JS, app móvil, facturación electrónica del cobro al tenant.
