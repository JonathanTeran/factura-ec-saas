<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payment\PayPal\PayPalCheckoutService;
use App\Services\Payment\PayPal\PayPalClient;
use App\Services\Payment\PayPal\PayPalException;
use App\Services\Settings\PayPalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de PayPal. Público: la autenticidad se comprueba con la API
 * verify-webhook-signature antes de tocar cualquier pago.
 */
class PayPalWebhookController extends Controller
{
    private const SIGNATURE_HEADERS = [
        'paypal-auth-algo',
        'paypal-cert-url',
        'paypal-transmission-id',
        'paypal-transmission-sig',
        'paypal-transmission-time',
    ];

    public function __invoke(
        Request $request,
        PayPalSettings $settings,
        PayPalClient $client,
        PayPalCheckoutService $checkout,
    ): JsonResponse {
        if (! $settings->isConfigured() || $settings->webhookId() === '') {
            Log::warning('PayPal webhook recibido sin credenciales o sin ID de webhook configurado.');

            return response()->json(['received' => false], 503);
        }

        $raw = $request->getContent();
        $headers = [];
        foreach (self::SIGNATURE_HEADERS as $header) {
            $headers[$header] = $request->header($header);
        }

        try {
            $valid = $client->verifyWebhookSignature($headers, $raw);
        } catch (PayPalException $e) {
            Log::warning('PayPal webhook: no se pudo verificar la firma', ['issue' => $e->issue, 'status' => $e->status]);

            return response()->json(['received' => false], $e->isTransient() ? 503 : 400);
        }

        if (! $valid) {
            Log::warning('PayPal webhook con firma inválida', ['transmission_id' => $headers['paypal-transmission-id']]);

            return response()->json(['received' => false], 400);
        }

        $event = json_decode($raw, true);

        try {
            $action = $checkout->handleWebhook(is_array($event) ? $event : []);
        } catch (\Throwable $e) {
            report($e);

            // 5xx: PayPal reintenta la entrega más tarde.
            return response()->json(['received' => false], 500);
        }

        Log::info('PayPal webhook procesado', [
            'event_id' => $event['id'] ?? null,
            'event_type' => $event['event_type'] ?? null,
            'action' => $action,
        ]);

        return response()->json(['received' => true]);
    }
}
