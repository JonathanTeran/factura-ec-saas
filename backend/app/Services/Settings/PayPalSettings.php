<?php

namespace App\Services\Settings;

use App\Models\Billing\PaymentMethodSetting;
use App\Models\SystemSetting;
use App\Services\Landing\PublicLandingCache;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Credenciales y estado de PayPal para cobrar las suscripciones.
 *
 * Administrable por el super admin (Filament → Sistema → PayPal). Sin valor
 * guardado se usan PAYPAL_* del .env (config/billing.php). El Secret se guarda
 * cifrado con APP_KEY y nunca se devuelve al navegador. El interruptor de
 * activación es la fila `paypal` de payment_method_settings.
 */
class PayPalSettings
{
    private const CACHE_KEY = 'system_settings:paypal';

    private const GROUP = 'paypal';

    public const METHOD_CODE = 'paypal';

    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    /**
     * @return array<string, array{key: string, type: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            'mode' => [
                'key' => 'paypal.mode',
                'type' => 'string',
                'description' => 'Entorno de PayPal: sandbox (pruebas) o live (cobros reales).',
            ],
            'client_id' => [
                'key' => 'paypal.client_id',
                'type' => 'string',
                'description' => 'Client ID de la app REST de PayPal.',
            ],
            'client_secret' => [
                'key' => 'paypal.client_secret',
                'type' => 'encrypted',
                'description' => 'Secret de la app REST de PayPal (cifrado con APP_KEY).',
            ],
            'webhook_id' => [
                'key' => 'paypal.webhook_id',
                'type' => 'string',
                'description' => 'ID del webhook de PayPal que apunta a /api/v1/webhooks/paypal.',
            ],
        ];
    }

    public function mode(): string
    {
        $mode = (string) ($this->stored('mode') ?? config('billing.paypal.mode', self::MODE_SANDBOX));

        return $mode === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX;
    }

    public function isSandbox(): bool
    {
        return $this->mode() === self::MODE_SANDBOX;
    }

    public function clientId(): string
    {
        return trim((string) ($this->stored('client_id') ?? config('billing.paypal.client_id', '')));
    }

    public function clientSecret(): string
    {
        $encrypted = $this->stored('client_secret');

        if ($encrypted === null || $encrypted === '') {
            return trim((string) config('billing.paypal.client_secret', ''));
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            Log::warning('PayPal: no se pudo descifrar el Secret guardado (¿cambió APP_KEY?).');

            return '';
        }
    }

    public function hasClientSecret(): bool
    {
        return $this->clientSecret() !== '';
    }

    public function webhookId(): string
    {
        return trim((string) ($this->stored('webhook_id') ?? config('billing.paypal.webhook_id', '')));
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->hasClientSecret();
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->cached()['enabled'] ?? false);
    }

    /** Se ofrece en el panel solo si está activado y tiene credenciales. */
    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->isConfigured();
    }

    public function apiBaseUrl(): string
    {
        return $this->isSandbox() ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/v1/webhooks/paypal';
    }

    /**
     * Guarda lo recibido. `client_secret` vacío conserva el actual; `enabled`
     * (opcional) cambia la fila paypal de payment_method_settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        foreach (['mode', 'client_id', 'webhook_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) $data[$field]);
                if ($field === 'mode') {
                    $value = $value === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX;
                }
                $this->put($field, $value);
            }
        }

        $secret = trim((string) ($data['client_secret'] ?? ''));
        if ($secret !== '') {
            $this->put('client_secret', Crypt::encryptString($secret));
        }

        if (array_key_exists('enabled', $data)) {
            $this->setEnabled((bool) $data['enabled']);
        }

        self::forget();
    }

    public function setWebhookId(string $webhookId): void
    {
        $this->put('webhook_id', trim($webhookId));
        self::forget();
    }

    public function setEnabled(bool $enabled): void
    {
        PaymentMethodSetting::query()->updateOrCreate(
            ['code' => self::METHOD_CODE],
            ['name' => 'PayPal', 'requires_gateway' => true, 'is_enabled' => $enabled],
        );

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        PublicLandingCache::forget();
    }

    private function stored(string $field): ?string
    {
        $key = self::definitions()[$field]['key'];

        return $this->cached()['values'][$key] ?? null;
    }

    /**
     * Valores tal como están en BD (el Secret sigue cifrado dentro del caché).
     *
     * @return array{values: array<string, string>, enabled: bool}
     */
    private function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $values = SystemSetting::query()
                ->group(self::GROUP)
                ->whereIn('key', array_column(self::definitions(), 'key'))
                ->pluck('value', 'key')
                ->map(fn ($value) => (string) $value)
                ->all();

            $enabled = (bool) PaymentMethodSetting::query()
                ->where('code', self::METHOD_CODE)
                ->value('is_enabled');

            return ['values' => $values, 'enabled' => $enabled];
        });
    }

    private function put(string $field, string $value): void
    {
        $definition = self::definitions()[$field];

        SystemSetting::query()->updateOrCreate(
            ['key' => $definition['key']],
            [
                'value' => $value,
                'type' => $definition['type'],
                'group_name' => self::GROUP,
                'description' => $definition['description'],
                'updated_at' => now(),
            ],
        );
    }
}
