<?php

namespace App\Services\Payment\PayPal;

use App\Services\Settings\PayPalSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo de la API REST de PayPal (Orders v2, Payments v2, webhooks).
 * El token OAuth se cachea por entorno y Client ID; ante un 401 se renueva una vez.
 */
class PayPalClient
{
    public function __construct(
        private PayPalSettings $settings,
    ) {}

    public function accessToken(bool $fresh = false): string
    {
        if (! $this->settings->isConfigured()) {
            throw PayPalException::notConfigured();
        }

        $cacheKey = 'paypal:token:'.$this->settings->mode().':'.sha1($this->settings->clientId());

        if ($fresh) {
            Cache::forget($cacheKey);
        } elseif (is_string($token = Cache::get($cacheKey)) && $token !== '') {
            return $token;
        }

        try {
            $response = $this->http()
                ->asForm()
                ->withBasicAuth($this->settings->clientId(), $this->settings->clientSecret())
                ->post($this->url('/v1/oauth2/token'), ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $e) {
            throw new PayPalException('No se pudo conectar con PayPal. Intenta de nuevo en unos minutos.');
        }

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw PayPalException::fromResponse($response, 'PayPal rechazó las credenciales. Revisa el Client ID, el Secret y el entorno (sandbox o live).');
        }

        $token = $response->json('access_token');
        // Renovamos 5 minutos antes de que venza (PayPal da ~9 horas).
        Cache::put($cacheKey, $token, max(60, (int) $response->json('expires_in', 3600) - 300));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload, string $requestId): array
    {
        return $this->send('POST', '/v2/checkout/orders', $payload, [
            'PayPal-Request-Id' => $requestId,
            'Prefer' => 'return=representation',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function captureOrder(string $orderId, string $requestId): array
    {
        return $this->send('POST', '/v2/checkout/orders/'.rawurlencode($orderId).'/capture', [], [
            'PayPal-Request-Id' => $requestId,
            'Prefer' => 'return=representation',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        return $this->send('GET', '/v2/checkout/orders/'.rawurlencode($orderId));
    }

    /**
     * Reembolsa una captura. Sin `$amount` es reembolso total.
     *
     * @param  array{value: string, currency_code: string}|null  $amount
     * @return array<string, mixed>
     */
    public function refundCapture(string $captureId, ?array $amount, ?string $noteToPayer, string $requestId): array
    {
        $body = array_filter([
            'amount' => $amount,
            'note_to_payer' => $noteToPayer !== null ? mb_substr($noteToPayer, 0, 255) : null,
        ]);

        return $this->send('POST', '/v2/payments/captures/'.rawurlencode($captureId).'/refund', $body, [
            'PayPal-Request-Id' => $requestId,
            'Prefer' => 'return=representation',
        ]);
    }

    /**
     * Verifica la firma de un webhook con la API de PayPal. El evento se envía
     * con el cuerpo CRUDO recibido: re-serializar el JSON rompe la verificación.
     *
     * @param  array<string, string|null>  $headers  cabeceras paypal-* en minúsculas
     */
    public function verifyWebhookSignature(array $headers, string $rawBody): bool
    {
        $webhookId = $this->settings->webhookId();
        $required = ['paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id', 'paypal-transmission-sig', 'paypal-transmission-time'];

        if ($webhookId === '' || trim($rawBody) === '' || json_decode($rawBody) === null) {
            return false;
        }

        foreach ($required as $header) {
            if (! is_string($headers[$header] ?? null) || $headers[$header] === '') {
                return false;
            }
        }

        $envelope = json_encode([
            'auth_algo' => $headers['paypal-auth-algo'],
            'cert_url' => $headers['paypal-cert-url'],
            'transmission_id' => $headers['paypal-transmission-id'],
            'transmission_sig' => $headers['paypal-transmission-sig'],
            'transmission_time' => $headers['paypal-transmission-time'],
            'webhook_id' => $webhookId,
            'webhook_event' => '__RAW_EVENT__',
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $body = str_replace('"__RAW_EVENT__"', $rawBody, $envelope);

        $result = $this->send('POST', '/v1/notifications/verify-webhook-signature', null, [], $body);

        return ($result['verification_status'] ?? null) === 'SUCCESS';
    }

    /**
     * @param  list<string>  $eventTypes
     * @return array<string, mixed>
     */
    public function createWebhook(string $url, array $eventTypes): array
    {
        return $this->send('POST', '/v1/notifications/webhooks', [
            'url' => $url,
            'event_types' => array_map(fn (string $name) => ['name' => $name], $eventTypes),
        ]);
    }

    /**
     * Reemplaza los eventos a los que está suscrito un webhook existente.
     *
     * @param  list<string>  $eventTypes
     * @return array<string, mixed>
     */
    public function updateWebhookEvents(string $webhookId, array $eventTypes): array
    {
        return $this->send('PATCH', '/v1/notifications/webhooks/'.rawurlencode($webhookId), null, [], json_encode([[
            'op' => 'replace',
            'path' => '/event_types',
            'value' => array_map(fn (string $name) => ['name' => $name], $eventTypes),
        ]], JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listWebhooks(): array
    {
        return array_values($this->send('GET', '/v1/notifications/webhooks')['webhooks'] ?? []);
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, ?array $json = null, array $headers = [], ?string $rawJson = null): array
    {
        $attempt = function (string $token) use ($method, $path, $json, $headers, $rawJson): Response {
            $request = $this->http()->withToken($token)->withHeaders($headers);

            if ($method !== 'GET') {
                // Captura sin cuerpo: PayPal exige JSON válido ("{}"), no "[]".
                $body = $rawJson ?? ($json === [] || $json === null ? '{}' : json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                $request = $request->withBody($body, 'application/json');
            }

            return $request->send($method, $this->url($path));
        };

        try {
            $response = $attempt($this->accessToken());

            if ($response->status() === 401) {
                $response = $attempt($this->accessToken(fresh: true));
            }
        } catch (ConnectionException $e) {
            throw new PayPalException('No se pudo conectar con PayPal. Intenta de nuevo en unos minutos.');
        }

        if (! $response->successful()) {
            throw PayPalException::fromResponse($response);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->connectTimeout(10)->timeout(30);
    }

    private function url(string $path): string
    {
        return $this->settings->apiBaseUrl().$path;
    }
}
