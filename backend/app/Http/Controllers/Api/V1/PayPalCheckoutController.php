<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PaymentResource;
use App\Models\Billing\Payment;
use App\Models\Billing\Plan;
use App\Services\Billing\SubscriptionQuote;
use App\Services\Payment\PayPal\PayPalCaptureResult;
use App\Services\Payment\PayPal\PayPalCheckoutService;
use App\Services\Payment\PayPal\PayPalException;
use App\Services\Settings\PayPalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Suscripciones y Facturación
 */
class PayPalCheckoutController extends ApiController
{
    public function __construct(
        private PayPalCheckoutService $checkout,
        private PayPalSettings $settings,
    ) {}

    /**
     * Métodos de pago disponibles para contratar un plan
     */
    public function options(): JsonResponse
    {
        return $this->success([
            'bank_transfer' => ['enabled' => true],
            'paypal' => [
                'enabled' => $this->settings->isAvailable(),
                'sandbox' => $this->settings->isSandbox(),
            ],
            'tax_rate' => SubscriptionQuote::TAX_RATE,
        ]);
    }

    /**
     * Iniciar pago con PayPal
     *
     * Crea la orden en PayPal y devuelve la URL a la que se redirige al cliente.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'billing_name' => 'required|string|max:300',
            'billing_email' => 'required|email|max:255',
            'billing_identification' => 'nullable|string|max:20',
            'coupon_code' => 'nullable|string|max:50',
        ], [
            'plan_id.required' => 'El plan es requerido.',
            'plan_id.exists' => 'El plan seleccionado no existe.',
            'billing_cycle.required' => 'El ciclo de facturación es requerido.',
            'billing_cycle.in' => 'El ciclo debe ser mensual o anual.',
            'billing_name.required' => 'El nombre de facturación es requerido.',
            'billing_email.required' => 'El correo de facturación es requerido.',
            'billing_email.email' => 'El correo de facturación no es válido.',
        ]);

        $tenant = $request->user()->tenant;
        $plan = Plan::active()->where('is_contact_sales', false)->find($data['plan_id']);

        if (! $plan) {
            return $this->error('El plan seleccionado no está disponible.', 422);
        }

        if ($review = Payment::where('tenant_id', $tenant->id)->inReview()->latest()->first()) {
            return $this->error(
                'Ya tienes un pago en revisión. Te avisaremos por correo cuando se confirme; no es necesario pagar de nuevo.',
                422,
                ['pending_payment_id' => $review->id],
            );
        }

        try {
            $result = $this->checkout->createOrder(
                $tenant,
                $plan,
                $data['billing_cycle'],
                [
                    'name' => $data['billing_name'],
                    'email' => $data['billing_email'],
                    'identification' => $data['billing_identification'] ?? null,
                ],
                $data['coupon_code'] ?? null,
            );
        } catch (PayPalException $e) {
            return $this->error($e->getMessage(), $e->status === 422 ? 422 : 502);
        }

        $quote = $result['quote'];

        return $this->created([
            'payment_id' => $result['payment']->id,
            'order_id' => $result['payment']->gateway_payment_id,
            'approve_url' => $result['approve_url'],
            'amounts' => [
                'subtotal' => $quote->subtotal,
                'discount' => $quote->discount,
                'tax' => $quote->tax,
                'total' => $quote->total,
                'currency' => 'USD',
            ],
        ], 'Te llevamos a PayPal para completar el pago.');
    }

    /**
     * Confirmar pago de PayPal
     *
     * Captura la orden aprobada y activa el plan. Es seguro repetirlo.
     */
    public function capture(Request $request, string $orderId): JsonResponse
    {
        $payment = $this->tenantPayment($request, $orderId);
        $result = $this->checkout->capture($payment);

        $payload = [
            'status' => $result->status,
            'payment' => new PaymentResource($result->payment->fresh(['subscription.plan'])),
        ];

        return match ($result->status) {
            PayPalCaptureResult::COMPLETED => $this->success($payload, $result->message),
            PayPalCaptureResult::PROCESSING => $this->success($payload, $result->message, 202),
            default => $this->error($result->message, 422, ['status' => PayPalCaptureResult::FAILED]),
        };
    }

    /**
     * Cancelar pago de PayPal
     */
    public function cancel(Request $request, string $orderId): JsonResponse
    {
        $payment = $this->tenantPayment($request, $orderId);
        $this->checkout->cancel($payment);

        return $this->success([
            'payment' => new PaymentResource($payment->fresh()),
        ], 'Cancelaste el pago con PayPal. No se realizó ningún cobro.');
    }

    private function tenantPayment(Request $request, string $orderId): Payment
    {
        return Payment::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('gateway', 'paypal')
            ->where('gateway_payment_id', $orderId)
            ->firstOrFail();
    }
}
