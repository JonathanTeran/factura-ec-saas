<?php

namespace Tests\Traits;

use App\Services\Settings\PayPalSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * PayPal en tests: credenciales de sandbox falsas y API simulada por ruta.
 * Las rutas no simuladas fallan (preventStrayRequests): nunca sale a PayPal.
 */
trait FakesPayPal
{
    protected function configurePayPal(bool $enabled = true, array $overrides = []): void
    {
        PayPalSettings::forget();

        app(PayPalSettings::class)->save(array_merge([
            'enabled' => $enabled,
            'mode' => 'sandbox',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'webhook_id' => 'WH-TEST-123',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $routes  "METHOD /ruta/regex" => respuesta o closure(Request)
     */
    protected function fakePayPal(array $routes = []): void
    {
        Http::preventStrayRequests();

        Http::fake(function (Request $request) use ($routes) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if ($path === '/v1/oauth2/token') {
                return Http::response(['access_token' => 'A21-test-token', 'token_type' => 'Bearer', 'expires_in' => 32400]);
            }

            foreach ($routes as $key => $response) {
                [$method, $pattern] = explode(' ', $key, 2);

                if ($request->method() === $method && preg_match('#^'.$pattern.'$#', $path)) {
                    return is_callable($response) ? $response($request) : $response;
                }
            }

            return null;
        });
    }

    protected function paypalOrderCreated(string $orderId): mixed
    {
        return Http::response([
            'id' => $orderId,
            'status' => 'PAYER_ACTION_REQUIRED',
            'links' => [
                ['href' => "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$orderId}", 'rel' => 'self', 'method' => 'GET'],
                ['href' => "https://www.sandbox.paypal.com/checkoutnow?token={$orderId}", 'rel' => 'payer-action', 'method' => 'GET'],
            ],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    protected function capturedOrderBody(string $orderId, string $captureId, string $amount, string $status = 'COMPLETED', ?int $customId = null): array
    {
        return [
            'id' => $orderId,
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'reference_id' => 'PAY-TEST',
                'payments' => [
                    'captures' => [array_filter([
                        'id' => $captureId,
                        'status' => $status,
                        'amount' => ['currency_code' => 'USD', 'value' => $amount],
                        'custom_id' => $customId !== null ? (string) $customId : null,
                        'final_capture' => true,
                    ], fn ($value) => $value !== null)],
                ],
            ]],
        ];
    }

    protected function paypalError(int $status, string $issue, string $description = 'Error simulado'): mixed
    {
        return Http::response([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => $issue, 'description' => $description]],
            'debug_id' => 'debug-test',
        ], $status);
    }
}
