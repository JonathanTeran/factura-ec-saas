<?php

namespace App\Services\Billing;

use App\Enums\PaymentMethod;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * For bank transfer payments, no gateway processing is needed.
     * Returns a pending_approval result.
     */
    public function charge(array $data): array
    {
        Log::info('Payment charge requested', [
            'amount' => $data['amount'],
            'method' => $data['payment_method'] ?? 'transfer',
        ]);

        return [
            'success' => true,
            'transaction_id' => 'TRF-' . now()->format('Ymd') . '-' . bin2hex(random_bytes(6)),
            'status' => 'pending_approval',
            'amount' => $data['amount'],
            'requires_approval' => true,
        ];
    }

    /**
     * Cancel subscription - no-op for transfer-only.
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        return true;
    }

    /**
     * Refund - manual process for bank transfers.
     */
    public function refund(string $transactionId, float $amount): array
    {
        return [
            'refund_id' => 'REF-' . bin2hex(random_bytes(6)),
            'status' => 'pending',
            'amount' => $amount,
            'note' => 'Reembolso por transferencia bancaria - proceso manual',
        ];
    }
}
