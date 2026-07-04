<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Textos editoriales de la seccion de precios de la landing, administrables
 * desde el super admin (Filament). Si no hay valor guardado, se usa el default.
 */
class PricingContentSettings
{
    private const CACHE_KEY = 'system_settings:pricing_content';

    private const GROUP = 'landing_pricing';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'eyebrow' => [
                'key' => 'landing.pricing.eyebrow',
                'type' => 'string',
                'description' => 'Etiqueta pequeña sobre el título (ej: Planes).',
                'default' => 'Planes',
            ],
            'title' => [
                'key' => 'landing.pricing.title',
                'type' => 'string',
                'description' => 'Título principal de la sección de precios.',
                'default' => 'Precios transparentes, sin sorpresas',
            ],
            'subtitle' => [
                'key' => 'landing.pricing.subtitle',
                'type' => 'string',
                'description' => 'Subtítulo bajo el título.',
                'default' => 'Sin comisiones por documento. Escoge el plan que se ajuste a tu negocio.',
            ],
            'badge_enabled' => [
                'key' => 'landing.pricing.badge_enabled',
                'type' => 'boolean',
                'description' => 'Mostrar el badge diferenciador bajo el subtítulo.',
                'default' => true,
            ],
            'badge_text' => [
                'key' => 'landing.pricing.badge_text',
                'type' => 'string',
                'description' => 'Texto del badge diferenciador.',
                'default' => 'Administra varias empresas (RUCs) desde una sola cuenta',
            ],
            'footer_note' => [
                'key' => 'landing.pricing.footer_note',
                'type' => 'string',
                'description' => 'Nota al pie de la sección de precios.',
                'default' => 'Todos los planes incluyen soporte por email. Pago seguro por transferencia bancaria.',
            ],
        ];
    }

    /**
     * Valores resueltos (guardado o default), cacheados.
     *
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
            default => (string) $value,
        };
    }
}
