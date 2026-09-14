<?php

namespace App\Services\Payment\PayPal;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Notifications\AdminEventNotification;
use App\Notifications\PaymentApprovedNotification;
use App\Services\Billing\SubscriptionActivator;
use App\Services\Billing\SubscriptionQuote;
use App\Services\Cache\TenantCacheService;
use App\Services\Notification\NotificationService;
use App\Services\Settings\PayPalSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cobro de suscripciones con PayPal (Orders API v2, pago único por periodo).
 *
 * 1. createOrder(): pago PENDING + orden en PayPal → URL para aprobar.
 * 2. capture(): al volver de PayPal (o por webhook) se captura y, si el cobro
 *    quedó COMPLETED por el monto esperado, se activa el plan una sola vez.
 * 3. handleWebhook(): red de seguridad (comprador que no vuelve, capturas en
 *    revisión, reembolsos y contracargos).
 */
class PayPalCheckoutService
{
    /** Eventos que registra "Registrar webhook" en Filament. */
    public const WEBHOOK_EVENTS = [
        'CHECKOUT.ORDER.APPROVED',
        'PAYMENT.CAPTURE.COMPLETED',
        'PAYMENT.CAPTURE.PENDING',
        'PAYMENT.CAPTURE.DENIED',
        'PAYMENT.CAPTURE.DECLINED',
        'PAYMENT.CAPTURE.REFUNDED',
        'PAYMENT.CAPTURE.REVERSED',
    ];

    private const MISMATCH_REASON = 'El cobro de PayPal no coincide con el pago esperado; requiere revisión manual.';

    private const DECLINE_ISSUES = [
        'INSTRUMENT_DECLINED',
        'PAYER_CANNOT_PAY',
        'TRANSACTION_REFUSED',
        'PAYEE_BLOCKED_TRANSACTION',
        'COMPLIANCE_VIOLATION',
        'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED',
    ];

    public function __construct(
        private PayPalClient $client,
        private PayPalSettings $settings,
        private SubscriptionActivator $activator,
    ) {}

    /**
     * @param  array{name: string, email: string, identification?: string|null}  $billing
     * @return array{payment: Payment, approve_url: string, quote: SubscriptionQuote}
     */
    public function createOrder(Tenant $tenant, Plan $plan, string $billingCycle, array $billing, ?string $couponCode = null): array
    {
        if (! $this->settings->isAvailable()) {
            throw new PayPalException('El pago con PayPal no está disponible en este momento. Usa transferencia bancaria.', 'NOT_AVAILABLE', 422);
        }

        $quote = SubscriptionQuote::make($plan, $billingCycle, $tenant->id, $couponCode);

        if ($quote->total <= 0) {
            throw new PayPalException('Este plan no tiene un monto a pagar.', 'ZERO_AMOUNT', 422);
        }

        // Intentos anteriores que nunca se aprobaron: los reemplaza este.
        Payment::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_method', PaymentMethod::PAYPAL->value)
            ->where('status', PaymentStatus::PENDING->value)
            ->update([
                'status' => PaymentStatus::CANCELED->value,
                'failure_reason' => 'Reemplazado por un nuevo intento de pago con PayPal.',
                'failed_at' => now(),
            ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::PAYPAL,
            'gateway' => 'paypal',
            'amount' => $quote->subtotal,
            'tax_amount' => $quote->tax,
            'total_amount' => $quote->total,
            'currency' => 'USD',
            'billing_name' => $billing['name'],
            'billing_email' => $billing['email'],
            'billing_identification' => $billing['identification'] ?? null,
            'description' => "PayPal · Plan {$plan->name} ({$quote->cycleLabel()})",
            'metadata' => [
                'checkout' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'billing_cycle' => $quote->billingCycle,
                    'coupon_code' => $quote->coupon?->code,
                    'base_price' => $quote->basePrice,
                    'discount' => $quote->discount,
                    'mode' => $this->settings->mode(),
                ],
            ],
        ]);

        try {
            $order = $this->client->createOrder($this->orderPayload($payment, $quote), "facturon-order-{$payment->id}");
        } catch (PayPalException $e) {
            $this->fail($payment, 'No se pudo crear la orden en PayPal: '.$e->getMessage());
            Log::warning('PayPal: error al crear la orden', ['payment_id' => $payment->id, 'issue' => $e->issue, 'debug_id' => $e->debugId]);

            throw $e;
        }

        $approveUrl = collect($order['links'] ?? [])
            ->first(fn ($link) => in_array($link['rel'] ?? null, ['payer-action', 'approve'], true))['href'] ?? null;

        if (! is_string($order['id'] ?? null) || ! is_string($approveUrl)) {
            $this->fail($payment, 'PayPal no devolvió el enlace de aprobación.');

            throw new PayPalException('PayPal no devolvió el enlace para aprobar el pago. Intenta de nuevo.', 'NO_APPROVE_LINK', 502);
        }

        $payment->update([
            'gateway_payment_id' => $order['id'],
            'gateway_response' => ['order' => Arr::only($order, ['id', 'status', 'create_time'])],
        ]);

        return ['payment' => $payment->fresh(), 'approve_url' => $approveUrl, 'quote' => $quote];
    }

    /**
     * Captura la orden y activa el plan. Idempotente: un pago ya completado se
     * devuelve tal cual (el regreso desde PayPal y el webhook pueden coincidir).
     */
    public function capture(Payment $payment): PayPalCaptureResult
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $payment */
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::COMPLETED) {
                return PayPalCaptureResult::completed($payment);
            }

            if (in_array($payment->status, [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED], true)) {
                return PayPalCaptureResult::failed($payment, 'Este pago fue reembolsado.');
            }

            $orderId = (string) $payment->gateway_payment_id;

            if ($payment->status === PaymentStatus::PROCESSING) {
                return $this->recheckProcessing($payment, $orderId);
            }

            try {
                $order = $this->client->captureOrder($orderId, "facturon-capture-{$payment->id}");
            } catch (PayPalException $e) {
                if (! $e->hasIssue('ORDER_ALREADY_CAPTURED')) {
                    return $this->captureError($payment, $e);
                }

                $order = $this->client->getOrder($orderId);
            }

            $capture = data_get($order, 'purchase_units.0.payments.captures.0');

            if (! is_array($capture)) {
                return PayPalCaptureResult::failed($payment, 'PayPal todavía no confirma el cobro. Si ya aprobaste el pago, lo activaremos automáticamente en unos minutos.');
            }

            return $this->applyCapture($payment, $capture);
        });
    }

    /**
     * Pago en revisión: si PayPal ya completó el cobro por el monto esperado
     * (y el webhook no llegó), se activa ahora; si no, sigue en revisión.
     */
    private function recheckProcessing(Payment $payment, string $orderId): PayPalCaptureResult
    {
        $inReview = PayPalCaptureResult::processing($payment, 'Tu pago está en revisión. Te avisaremos por correo cuando se confirme.');

        if ($orderId === '') {
            return $inReview;
        }

        try {
            $capture = data_get($this->client->getOrder($orderId), 'purchase_units.0.payments.captures.0');
        } catch (PayPalException) {
            return $inReview;
        }

        if (is_array($capture) && strtoupper((string) ($capture['status'] ?? '')) === 'COMPLETED' && $this->captureMatches($payment, $capture)) {
            return $this->complete($payment, (string) ($capture['id'] ?? ''));
        }

        return $inReview;
    }

    /** El comprador canceló en PayPal: el intento queda cancelado, sin cobro. */
    public function cancel(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::PENDING) {
            $payment->update([
                'status' => PaymentStatus::CANCELED,
                'failure_reason' => 'Cancelado por el cliente en PayPal.',
                'failed_at' => now(),
            ]);
        }
    }

    /**
     * Procesa un evento de webhook ya verificado. Devuelve la acción tomada.
     *
     * @param  array<string, mixed>  $event
     */
    public function handleWebhook(array $event): string
    {
        $type = (string) ($event['event_type'] ?? '');
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];

        return match ($type) {
            'CHECKOUT.ORDER.APPROVED' => $this->onOrderApproved($resource),
            'PAYMENT.CAPTURE.COMPLETED',
            'PAYMENT.CAPTURE.PENDING',
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.DECLINED' => $this->onCaptureEvent($resource),
            'PAYMENT.CAPTURE.REFUNDED',
            'PAYMENT.CAPTURE.REVERSED' => $this->onRefundEvent($resource, $type),
            default => 'ignored',
        };
    }

    /**
     * Reembolso desde el admin. Sin error, PayPal aceptó el reembolso.
     *
     * @throws PayPalException
     */
    public function refund(Payment $payment, float $amount, string $reason): void
    {
        $captureId = (string) $payment->gateway_transaction_id;

        if ($captureId === '' || $payment->payment_method !== PaymentMethod::PAYPAL) {
            throw new PayPalException('Este pago no tiene un cobro de PayPal para reembolsar.', 'NO_CAPTURE');
        }

        $total = (float) $payment->total_amount;
        $amount = min($amount, $total);
        $isFull = $amount >= $total - 0.005;

        $refund = $this->client->refundCapture(
            $captureId,
            $isFull ? null : ['value' => SubscriptionQuote::money($amount), 'currency_code' => $payment->currency ?: 'USD'],
            $reason,
            'facturon-refund-'.$payment->id.'-'.Str::uuid(),
        );

        $status = strtoupper((string) ($refund['status'] ?? ''));

        if (in_array($status, ['CANCELLED', 'FAILED'], true)) {
            throw new PayPalException("PayPal no completó el reembolso (estado {$status}).", $status);
        }

        $payment->refund($isFull ? $total : $amount, $reason);

        $response = $payment->gateway_response ?? [];
        $response['refunds'][] = Arr::only($refund, ['id', 'status', 'amount', 'create_time']);
        $payment->update(['gateway_response' => $response]);
    }

    /**
     * Crea el webhook de PayPal para esta instalación (o reutiliza el que ya
     * apunta a la misma URL, actualizando sus eventos) y guarda su ID.
     */
    public function registerWebhook(): string
    {
        $url = $this->settings->webhookUrl();

        try {
            $webhook = $this->client->createWebhook($url, self::WEBHOOK_EVENTS);
        } catch (PayPalException $e) {
            if (! $e->hasIssue('WEBHOOK_URL_ALREADY_EXISTS')) {
                throw $e;
            }

            $webhook = collect($this->client->listWebhooks())
                ->first(fn ($hook) => rtrim((string) ($hook['url'] ?? ''), '/') === rtrim($url, '/'));

            if (! is_array($webhook) || ! is_string($webhook['id'] ?? null)) {
                throw $e;
            }

            $this->client->updateWebhookEvents($webhook['id'], self::WEBHOOK_EVENTS);
        }

        $this->settings->setWebhookId((string) $webhook['id']);

        return (string) $webhook['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Payment $payment, SubscriptionQuote $quote): array
    {
        $money = fn (float $value) => ['currency_code' => 'USD', 'value' => SubscriptionQuote::money($value)];
        $item = "Plan {$quote->plan->name} ({$quote->cycleLabel()})";
        $panel = rtrim((string) config('support.frontend_url', config('app.url')), '/');

        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $payment->invoice_number,
                'custom_id' => (string) $payment->id,
                'description' => mb_substr("Facturón · {$item}", 0, 127),
                'amount' => [
                    ...$money($quote->total),
                    'breakdown' => [
                        'item_total' => $money($quote->subtotal),
                        'tax_total' => $money($quote->tax),
                    ],
                ],
                'items' => [[
                    'name' => mb_substr($item, 0, 127),
                    'quantity' => '1',
                    'unit_amount' => $money($quote->subtotal),
                    'tax' => $money($quote->tax),
                    'category' => 'DIGITAL_GOODS',
                ]],
            ]],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'brand_name' => 'Facturón',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'return_url' => "{$panel}/settings/subscription/paypal",
                        'cancel_url' => "{$panel}/settings/subscription?paypal=cancelled",
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $capture
     */
    private function applyCapture(Payment $payment, array $capture): PayPalCaptureResult
    {
        $captureId = (string) ($capture['id'] ?? '');
        $status = strtoupper((string) ($capture['status'] ?? ''));

        $response = $payment->gateway_response ?? [];
        $response['capture'] = Arr::only($capture, ['id', 'status', 'amount', 'status_details', 'create_time', 'update_time']);
        $payment->forceFill(['gateway_transaction_id' => $captureId ?: $payment->gateway_transaction_id, 'gateway_response' => $response])->save();

        if ($status === 'COMPLETED') {
            if (! $this->captureMatches($payment, $capture)) {
                $alreadyFlagged = $payment->status === PaymentStatus::PROCESSING
                    && str_starts_with((string) $payment->failure_reason, self::MISMATCH_REASON);

                $payment->update([
                    'status' => PaymentStatus::PROCESSING,
                    'failure_reason' => self::MISMATCH_REASON,
                ]);

                if (! $alreadyFlagged) {
                    $this->afterCommitNotifyAdmins('paypal.review_required', $payment, [
                        'reason' => 'Monto, moneda o referencia del cobro distintos a lo esperado.',
                        'captured' => data_get($capture, 'amount.value').' '.data_get($capture, 'amount.currency_code'),
                    ]);
                }

                return PayPalCaptureResult::processing($payment, 'Recibimos tu pago, pero necesitamos revisarlo. Te escribiremos en breve.');
            }

            return $this->complete($payment, $captureId);
        }

        if ($status === 'PENDING') {
            $payment->update(['status' => PaymentStatus::PROCESSING, 'failure_reason' => null]);

            return PayPalCaptureResult::processing($payment, 'PayPal está revisando tu pago. Tu plan se activará apenas lo confirme; te avisaremos por correo.');
        }

        $this->fail($payment, "PayPal rechazó el cobro (estado {$status}).");
        $this->afterCommitNotifyAdmins('paypal.payment_failed', $payment, ['reason' => "Captura en estado {$status}"]);

        return PayPalCaptureResult::failed($payment, 'PayPal rechazó el cobro. Intenta de nuevo con otro medio de pago.');
    }

    private function complete(Payment $payment, string $captureId): PayPalCaptureResult
    {
        $checkout = $payment->metadata['checkout'] ?? [];
        $plan = Plan::find($checkout['plan_id'] ?? null);

        if (! $plan) {
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'failure_reason' => 'Cobro recibido pero el plan ya no existe; asignar manualmente.',
            ]);
            $this->afterCommitNotifyAdmins('paypal.review_required', $payment, ['reason' => 'El plan del pago ya no existe.']);

            return PayPalCaptureResult::processing($payment, 'Recibimos tu pago y lo estamos revisando. Te escribiremos en breve.');
        }

        $payment->update([
            'status' => PaymentStatus::COMPLETED,
            'paid_at' => now(),
            'transaction_id' => $captureId,
            'gateway_transaction_id' => $captureId,
            'failure_reason' => null,
            'failed_at' => null,
        ]);

        $this->activator->activate($payment, $plan, (string) ($checkout['billing_cycle'] ?? 'monthly'), $checkout['coupon_code'] ?? null);

        $paymentId = $payment->id;
        DB::afterCommit(function () use ($paymentId) {
            $payment = Payment::with(['tenant.owner', 'subscription.plan'])->find($paymentId);

            if (! $payment) {
                return;
            }

            TenantCacheService::invalidateTenant($payment->tenant_id);
            $payment->tenant?->owner?->notify(new PaymentApprovedNotification($payment));
            $this->notifyAdmins('paypal.payment_completed', $payment);
        });

        return PayPalCaptureResult::completed($payment->fresh());
    }

    private function captureError(Payment $payment, PayPalException $e): PayPalCaptureResult
    {
        Log::warning('PayPal: no se pudo capturar la orden', [
            'payment_id' => $payment->id,
            'issue' => $e->issue,
            'status' => $e->status,
            'debug_id' => $e->debugId,
        ]);

        if ($e->hasIssue(...self::DECLINE_ISSUES)) {
            $this->fail($payment, 'PayPal rechazó el medio de pago ('.$e->issue.').');

            return PayPalCaptureResult::failed($payment, 'PayPal rechazó el medio de pago. Vuelve a intentarlo con otra tarjeta o con tu saldo PayPal.');
        }

        if ($e->hasIssue('ORDER_NOT_APPROVED', 'PAYER_ACTION_REQUIRED')) {
            return PayPalCaptureResult::failed($payment, 'Aún no aprobaste el pago en PayPal.');
        }

        if ($e->status === 404 || $e->hasIssue('RESOURCE_NOT_FOUND', 'INVALID_RESOURCE_ID', 'ORDER_EXPIRED')) {
            $this->fail($payment, 'La orden de PayPal ya no existe o expiró.');

            return PayPalCaptureResult::failed($payment, 'La orden de PayPal expiró. Inicia el pago de nuevo.');
        }

        return PayPalCaptureResult::failed($payment, 'PayPal no respondió. Si ya aprobaste el pago, lo confirmaremos automáticamente en unos minutos.');
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function onOrderApproved(array $resource): string
    {
        $payment = $this->findPayment(
            orderId: $resource['id'] ?? null,
            customId: data_get($resource, 'purchase_units.0.custom_id'),
        );

        if (! $payment) {
            return 'ignored';
        }

        if ($payment->status !== PaymentStatus::PENDING) {
            return 'noop';
        }

        return 'capture:'.$this->capture($payment)->status;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function onCaptureEvent(array $resource): string
    {
        $payment = $this->findPayment(
            orderId: data_get($resource, 'supplementary_data.related_ids.order_id'),
            customId: $resource['custom_id'] ?? null,
            captureId: $resource['id'] ?? null,
        );

        if (! $payment) {
            return 'ignored';
        }

        return DB::transaction(function () use ($payment, $resource) {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($payment->status, [PaymentStatus::COMPLETED, PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED], true)) {
                return 'noop';
            }

            return 'capture:'.$this->applyCapture($payment, $resource)->status;
        });
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function onRefundEvent(array $resource, string $type): string
    {
        $upLink = collect($resource['links'] ?? [])->first(fn ($link) => ($link['rel'] ?? null) === 'up')['href'] ?? '';
        $captureId = preg_match('#/captures/([^/?]+)#', (string) $upLink, $m) ? $m[1] : null;

        $payment = $this->findPayment(customId: $resource['custom_id'] ?? null, captureId: $captureId);

        if (! $payment) {
            return 'ignored';
        }

        $total = (float) $payment->total_amount;
        $refunded = (float) (data_get($resource, 'seller_payable_breakdown.total_refunded_amount.value')
            ?? data_get($resource, 'amount.value')
            ?? $total);
        $status = $refunded >= $total - 0.005 ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED;

        if ($payment->status === $status && abs((float) $payment->refund_amount - $refunded) < 0.005) {
            return 'noop';
        }

        $payment->update([
            'status' => $status,
            'refund_amount' => min($refunded, $total),
            'refunded_at' => $payment->refunded_at ?? now(),
            'refund_reason' => $payment->refund_reason
                ?? ($type === 'PAYMENT.CAPTURE.REVERSED' ? 'Contracargo o reversión en PayPal' : 'Reembolso registrado en PayPal'),
        ]);

        $this->notifyAdmins('paypal.refunded', $payment, [
            'kind' => $type === 'PAYMENT.CAPTURE.REVERSED' ? 'Contracargo o reversión' : 'Reembolso',
            'refunded' => SubscriptionQuote::money($refunded),
        ]);

        return 'refund:'.$status->value;
    }

    private function findPayment(mixed $orderId = null, mixed $customId = null, mixed $captureId = null): ?Payment
    {
        $query = fn () => Payment::query()->where('payment_method', PaymentMethod::PAYPAL->value);

        if (is_string($orderId) && $orderId !== '' && ($payment = $query()->where('gateway_payment_id', $orderId)->first())) {
            return $payment;
        }

        if (is_string($captureId) && $captureId !== '' && ($payment = $query()->where('gateway_transaction_id', $captureId)->first())) {
            return $payment;
        }

        if (is_numeric($customId) && ($payment = $query()->whereKey((int) $customId)->first())) {
            return $payment;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $capture
     */
    private function captureMatches(Payment $payment, array $capture): bool
    {
        $customId = $capture['custom_id'] ?? null;

        return data_get($capture, 'amount.value') === SubscriptionQuote::money($payment->total_amount)
            && strtoupper((string) data_get($capture, 'amount.currency_code')) === strtoupper($payment->currency ?: 'USD')
            && ($customId === null || (string) $customId === (string) $payment->id);
    }

    private function fail(Payment $payment, string $reason): void
    {
        // No usa Payment::markAsFailed() porque ese método borra gateway_response.
        $payment->update([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => mb_substr($reason, 0, 250),
            'failed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function afterCommitNotifyAdmins(string $event, Payment $payment, array $extra = []): void
    {
        $paymentId = $payment->id;
        DB::afterCommit(function () use ($event, $paymentId, $extra) {
            if ($payment = Payment::with(['tenant', 'subscription.plan'])->find($paymentId)) {
                $this->notifyAdmins($event, $payment, $extra);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function notifyAdmins(string $event, Payment $payment, array $extra = []): void
    {
        $data = [
            'tenant_name' => $payment->tenant?->name,
            'plan_name' => $payment->subscription?->plan?->name ?? ($payment->metadata['checkout']['plan_name'] ?? null),
            'amount' => SubscriptionQuote::money($payment->total_amount),
            'invoice_number' => $payment->invoice_number,
            'order_id' => $payment->gateway_payment_id,
            'capture_id' => $payment->gateway_transaction_id,
            'payment_id' => $payment->id,
            ...$extra,
        ];

        try {
            User::withoutGlobalScopes()->where('role', UserRole::SUPER_ADMIN)->get()
                ->each(fn (User $admin) => $admin->notify(new AdminEventNotification($event, $data)));
            NotificationService::notifyConfiguredAdmins(new AdminEventNotification($event, $data));
        } catch (\Throwable $e) {
            Log::warning('PayPal: no se pudo avisar a los admins', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
