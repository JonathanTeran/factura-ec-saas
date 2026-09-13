<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * RUC del proveedor del sistema de facturación (Facturón / AmePhia) que la
 * Resolución NAC-DGERCGC26-00000027 y la Ficha Técnica 2.34 del SRI obligan a
 * incluir en `infoAdicional` de TODOS los comprobantes electrónicos como
 * `<campoAdicional nombre="RUC Proveedor">…</campoAdicional>`.
 *
 * Administrable por el super admin (Filament → Sistema → RUC proveedor SRI);
 * sin valor guardado se usa SRI_PROVIDER_RUC del .env.
 */
class SriProviderSettings
{
    private const CACHE_KEY = 'system_settings:sri_provider';

    private const GROUP = 'sri_provider';

    /** Nombre del campo según la Ficha Técnica 2.34 del SRI. */
    public const FIELD_NAME = 'RUC Proveedor';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'enabled' => [
                'key' => 'sri.provider.enabled',
                'type' => 'boolean',
                'description' => 'Incluir el RUC del proveedor del sistema en la información adicional de los comprobantes.',
                'default' => true,
            ],
            'provider_ruc' => [
                'key' => 'sri.provider.ruc',
                'type' => 'string',
                'description' => 'RUC del proveedor del sistema de facturación (13 dígitos).',
                'default' => (string) config('sri.provider_ruc', ''),
            ],
            'field_name' => [
                'key' => 'sri.provider.field_name',
                'type' => 'string',
                'description' => 'Nombre del campoAdicional (Ficha Técnica 2.34: "RUC Proveedor").',
                'default' => self::FIELD_NAME,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = SystemSetting::query()
                ->group(self::GROUP)
                ->whereIn('key', array_column(self::definitions(), 'key'))
                ->get()
                ->keyBy('key');

            $resolved = [];

            foreach (self::definitions() as $field => $definition) {
                $setting = $stored->get($definition['key']);
                $resolved[$field] = $setting
                    ? $this->castValue($setting->value, $definition['type'])
                    : $definition['default'];
            }

            return $resolved;
        });
    }

    /** RUC efectivo (13 dígitos) o null si está desactivado o no configurado. */
    public function ruc(): ?string
    {
        $settings = $this->all();

        if (! $settings['enabled']) {
            return null;
        }

        $ruc = preg_replace('/\D+/', '', (string) $settings['provider_ruc']) ?? '';

        return preg_match('/^[0-9]{13}$/', $ruc) === 1 ? $ruc : null;
    }

    public function fieldName(): string
    {
        $name = trim((string) ($this->all()['field_name'] ?? ''));

        return $name !== '' ? $name : self::FIELD_NAME;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        foreach (self::definitions() as $field => $definition) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'value' => $this->prepareForStorage(Arr::get($data, $field, $definition['default']), $definition['type']),
                    'type' => $definition['type'],
                    'group_name' => self::GROUP,
                    'description' => $definition['description'],
                    'updated_at' => now(),
                ],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => (string) $value,
        };
    }

    private function prepareForStorage(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            default => trim((string) $value),
        };
    }
}
