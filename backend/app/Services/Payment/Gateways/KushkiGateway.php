<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KushkiGateway implements PaymentGatewayInterface
{
    private string $privateKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->privateKey = config('billing.kushki.private_key', '');
        $this->baseUrl = config('billing.kushki.sandbox', true) 
            ? 'https://sandbox-api.kushkipagos.com/card/v1' 
            : 'https://api.kushkipagos.com/card/v1';
    }

    public function processPayment(Subscription $subscription, array $paymentDetails): PaymentResult
    {
        try {
            $response = Http::withHeaders([
                'Private-Merchant-Id' => $this->privateKey,
            ])->post("{$this->baseUrl}/charges", [
                'token' => $paymentDetails['token'],
                'amount' => [
                    'subtotalIva' => 0,
                    'subtotalIva0' => $subscription->final_price,
                    'ice' => 0,
                    'iva' => 0,
                    'currency' => 'USD',
                ],
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return PaymentResult::success(
                    transactionId: $data['ticketNumber'],
                    gatewayPaymentId: $data['ticketNumber'],
                    gatewayResponse: $data,
                );
            }

            return PaymentResult::failure(
                $response->json()['message'] ?? 'Error en Kushki',
                $response->json()['code'] ?? (string)$response->status(),
                $response->json()
            );

        } catch (\Exception $e) {
            Log::error('Kushki payment error', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure($e->getMessage(), 'exception');
        }
    }

    public function createCustomer(array $customerData): string
    {
        // Kushki has a subscriptions API but for one-off charges we just need the token
        return $customerData['email'];
    }

    public function addPaymentMethod(string $customerId, string $paymentMethodToken): string
    {
        return $paymentMethodToken;
    }

    public function refund(Payment $payment, ?float $amount = null): PaymentResult
    {
        try {
            $response = Http::withHeaders([
                'Private-Merchant-Id' => $this->privateKey,
            ])->post("{$this->baseUrl}/refunds", [
                'ticketNumber' => $payment->gateway_payment_id,
                'amount' => [
                    'currency' => 'USD',
                    'total' => $amount ?? $payment->amount,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return PaymentResult::success(
                    transactionId: $data['ticketNumber'],
                    gatewayPaymentId: $data['ticketNumber'],
                    gatewayResponse: $data,
                );
            }

            return PaymentResult::failure(
                $response->json()['message'] ?? 'Error en reembolso Kushki',
                $response->json()['code'] ?? (string)$response->status(),
                $response->json()
            );

        } catch (\Exception $e) {
            Log::error('Kushki refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure($e->getMessage(), 'exception');
        }
    }

    public function getName(): string
    {
        return 'kushki';
    }
}
