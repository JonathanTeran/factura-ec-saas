<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('billing.stripe.secret'));
    }

    public function processPayment(Subscription $subscription, array $paymentDetails): PaymentResult
    {
        try {
            $amount = (int) ($subscription->final_price * 100); // Stripe uses cents

            $paymentIntentData = [
                'amount' => $amount,
                'currency' => 'usd',
                'description' => "Suscripción {$subscription->plan->name} - {$subscription->tenant->name}",
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                    'plan_id' => $subscription->plan_id,
                ],
            ];

            // If customer already exists in Stripe
            if (!empty($paymentDetails['customer_id'])) {
                $paymentIntentData['customer'] = $paymentDetails['customer_id'];
            }

            // If payment method is provided
            if (!empty($paymentDetails['payment_method'])) {
                $paymentIntentData['payment_method'] = $paymentDetails['payment_method'];
                $paymentIntentData['confirm'] = true;
                $paymentIntentData['return_url'] = config('app.url') . '/panel/billing/callback';
            }

            $paymentIntent = PaymentIntent::create($paymentIntentData);

            // Check if payment requires action (3D Secure, etc.)
            if ($paymentIntent->status === 'requires_action') {
                return PaymentResult::failure(
                    'Pago requiere autenticación adicional',
                    'requires_action',
                    [
                        'client_secret' => $paymentIntent->client_secret,
                        'requires_action' => true,
                    ]
                );
            }

            if ($paymentIntent->status === 'succeeded') {
                return PaymentResult::success(
                    transactionId: $paymentIntent->id,
                    gatewayPaymentId: $paymentIntent->id,
                    gatewayResponse: $paymentIntent->toArray(),
                );
            }

            return PaymentResult::failure(
                'Estado de pago inesperado: ' . $paymentIntent->status,
                $paymentIntent->status,
                $paymentIntent->toArray(),
            );

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment error', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);

            return PaymentResult::failure(
                $this->translateStripeError($e),
                $e->getStripeCode(),
                ['error' => $e->getMessage()],
            );
        }
    }

    public function createCustomer(array $customerData): string
    {
        try {
            $customer = Customer::create([
                'email' => $customerData['email'],
                'name' => $customerData['name'] ?? null,
                'metadata' => [
                    'tenant_id' => $customerData['tenant_id'] ?? null,
                ],
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe create customer error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function addPaymentMethod(string $customerId, string $paymentMethodToken): string
    {
        try {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodToken);
            $paymentMethod->attach(['customer' => $customerId]);

            // Set as default payment method
            Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod->id,
                ],
            ]);

            return $paymentMethod->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe add payment method error', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function refund(Payment $payment, ?float $amount = null): PaymentResult
    {
        try {
            $refundData = [
                'payment_intent' => $payment->gateway_payment_id,
            ];

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = Refund::create($refundData);

            return PaymentResult::success(
                transactionId: $refund->id,
                gatewayPaymentId: $refund->id,
                gatewayResponse: $refund->toArray(),
            );

        } catch (ApiErrorException $e) {
            Log::error('Stripe refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                $this->translateStripeError($e),
                $e->getStripeCode(),
                ['error' => $e->getMessage()],
            );
        }
    }

    public function getName(): string
    {
        return 'stripe';
    }

    protected function translateStripeError(ApiErrorException $e): string
    {
        return match ($e->getStripeCode()) {
            'card_declined' => 'Tarjeta rechazada. Por favor intente con otra tarjeta.',
            'expired_card' => 'Tarjeta expirada. Por favor actualice su información de pago.',
            'incorrect_cvc' => 'Código de seguridad incorrecto.',
            'insufficient_funds' => 'Fondos insuficientes.',
            'processing_error' => 'Error procesando el pago. Por favor intente nuevamente.',
            'invalid_card_type' => 'Tipo de tarjeta no soportado.',
            default => 'Error en el pago: ' . $e->getMessage(),
        };
    }
}
