<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPhoneGateway implements PaymentGatewayInterface
{
    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        $this->token = config('billing.payphone.token', '');
        $this->baseUrl = config('billing.payphone.sandbox', true) 
            ? 'https://pay.payphonetodoesposible.com/api' 
            : 'https://pay.payphonetodoesposible.com/api'; // Adjust if different for prod
    }

    public function processPayment(Subscription $subscription, array $paymentDetails): PaymentResult
    {
        try {
            $amount = (int) ($subscription->final_price * 100);

            // PayPhone usually works with a 'Sale' request or 'Prepare Sale'
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/Sale", [
                    'amount' => $amount,
                    'amountWithoutTax' => $amount,
                    'currency' => 'USD',
                    'clientTransactionId' => 'SUB-' . $subscription->id . '-' . time(),
                    'email' => $subscription->tenant->owner_email,
                    'phoneNumber' => $paymentDetails['phone'] ?? '',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return PaymentResult::success(
                    transactionId: $data['transactionId'],
                    gatewayPaymentId: $data['transactionId'],
                    gatewayResponse: $data,
                );
            }

            return PaymentResult::failure(
                $response->json()['message'] ?? 'Error en PayPhone',
                (string) $response->status(),
                $response->json()
            );

        } catch (\Exception $e) {
            Log::error('PayPhone payment error', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure($e->getMessage(), 'exception');
        }
    }

    public function createCustomer(array $customerData): string
    {
        // PayPhone doesn't typically require pre-creating customers in the same way Stripe does
        // but we can return the email or a unique identifier if needed.
        return $customerData['email'];
    }

    public function addPaymentMethod(string $customerId, string $paymentMethodToken): string
    {
        // PayPhone often uses a session-based or tokenized approach per transaction
        return $paymentMethodToken;
    }

    public function refund(Payment $payment, ?float $amount = null): PaymentResult
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/Reverse", [
                    'transactionId' => (int) $payment->gateway_payment_id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return PaymentResult::success(
                    transactionId: $data['transactionId'],
                    gatewayPaymentId: $data['transactionId'],
                    gatewayResponse: $data,
                );
            }

            return PaymentResult::failure(
                $response->json()['message'] ?? 'Error en reembolso PayPhone',
                (string) $response->status(),
                $response->json()
            );

        } catch (\Exception $e) {
            Log::error('PayPhone refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure($e->getMessage(), 'exception');
        }
    }

    public function getName(): string
    {
        return 'payphone';
    }
}
