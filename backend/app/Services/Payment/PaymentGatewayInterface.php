<?php

namespace App\Services\Payment;

use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;

interface PaymentGatewayInterface
{
    /**
     * Process a payment for a subscription.
     *
     * @param Subscription $subscription
     * @param array $paymentDetails Card token, customer ID, etc.
     * @return PaymentResult
     */
    public function processPayment(Subscription $subscription, array $paymentDetails): PaymentResult;

    /**
     * Create a customer in the payment gateway.
     *
     * @param array $customerData
     * @return string Customer ID in the gateway
     */
    public function createCustomer(array $customerData): string;

    /**
     * Add a payment method to a customer.
     *
     * @param string $customerId
     * @param string $paymentMethodToken
     * @return string Payment method ID
     */
    public function addPaymentMethod(string $customerId, string $paymentMethodToken): string;

    /**
     * Process a refund for a payment.
     *
     * @param Payment $payment
     * @param float|null $amount Null for full refund
     * @return PaymentResult
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResult;

    /**
     * Get the gateway name.
     *
     * @return string
     */
    public function getName(): string;
}
