<?php

namespace App\Services\Settings;

use App\Models\SRI\ElectronicDocument;
use App\Models\SystemSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class DocumentEmailTemplateSettings
{
    private const CACHE_KEY = 'system_settings:document_email_template';

    private const GROUP = 'email_templates';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'subject_template' => [
                'key' => 'mail.document_authorized.subject_template',
                'type' => 'string',
                'description' => 'Asunto del correo de documentos autorizados.',
                'default' => '{{document_type}} #{{document_number}} - {{company_name}}',
            ],
            'header_title' => [
                'key' => 'mail.document_authorized.header_title',
                'type' => 'string',
                'description' => 'Título principal del correo.',
                'default' => '{{company_name}}',
            ],
            'header_subtitle' => [
                'key' => 'mail.document_authorized.header_subtitle',
                'type' => 'string',
                'description' => 'Subtítulo debajo del encabezado.',
                'default' => 'RUC: {{company_ruc}}',
            ],
            'badge_text' => [
                'key' => 'mail.document_authorized.badge_text',
                'type' => 'string',
                'description' => 'Texto de la insignia superior.',
                'default' => 'Documento autorizado',
            ],
            'accent_color' => [
                'key' => 'mail.document_authorized.accent_color',
                'type' => 'string',
                'description' => 'Color principal del correo.',
                'default' => '#0284c7',
            ],
            'body_html' => [
                'key' => 'mail.document_authorized.body_html',
                'type' => 'string',
                'description' => 'Contenido HTML principal del correo.',
                'default' => <<<'HTML'
<p>Estimado/a <strong>{{customer_name}}</strong>,</p>
<p>Adjuntamos su {{document_type_lower}} <strong>{{document_number}}</strong>, autorizada por el Servicio de Rentas Internas (SRI).</p>
<p>Conserve este correo como respaldo de su comprobante electrónico. Encontrará adjuntos el archivo PDF (RIDE) y el XML autorizado cuando estén disponibles.</p>
HTML,
            ],
            'footer_html' => [
                'key' => 'mail.document_authorized.footer_html',
                'type' => 'string',
                'description' => 'Pie personalizado del correo.',
                'default' => <<<'HTML'
<p>{{company_name}}</p>
<p>{{company_address}}</p>
HTML,
            ],
            'cta_label' => [
                'key' => 'mail.document_authorized.cta_label',
                'type' => 'string',
                'description' => 'Texto del botón de llamada a la acción.',
                'default' => 'Ingresar al portal',
            ],
            'show_portal_button' => [
                'key' => 'mail.document_authorized.show_portal_button',
                'type' => 'boolean',
                'description' => 'Define si se muestra el botón al portal del cliente.',
                'default' => true,
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
                /** @var SystemSetting|null $setting */
                $setting = $stored->get($definition['key']);
                $resolved[$field] = $setting
                    ? $this->castValue($setting->value, $setting->type)
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

    /**
     * @return array<string, string>
     */
    public function placeholdersForDocument(ElectronicDocument $document): array
    {
        $customer = $document->customer;
        $company = $document->company;

        return [
            '{{customer_name}}' => e($customer->business_name ?: $customer->name ?: 'Cliente'),
            '{{company_name}}' => e($company->business_name ?: config('app.name')),
            '{{company_ruc}}' => e($company->ruc ?: 'N/A'),
            '{{company_address}}' => e($company->address ?: ''),
            '{{document_type}}' => e($document->document_type->label()),
            '{{document_type_lower}}' => e(strtolower($document->document_type->label())),
            '{{document_number}}' => e($document->getDocumentNumber()),
            '{{access_key}}' => e((string) $document->access_key),
            '{{issue_date}}' => e(optional($document->issue_date)->format('d/m/Y') ?: now()->format('d/m/Y')),
            '{{authorization_number}}' => e((string) ($document->authorization_number ?: $document->access_key)),
            '{{authorization_date}}' => e(optional($document->authorization_date)->format('d/m/Y H:i') ?: now()->format('d/m/Y H:i')),
            '{{total}}' => e(number_format((float) $document->total, 2)),
            '{{portal_url}}' => e(route('portal.login')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function previewPlaceholders(): array
    {
        return [
            '{{customer_name}}' => 'Comercial Ejemplo S.A.',
            '{{company_name}}' => 'AmePhia Factura',
            '{{company_ruc}}' => '1790012345001',
            '{{company_address}}' => 'Av. Amazonas y Naciones Unidas',
            '{{document_type}}' => 'Factura',
            '{{document_type_lower}}' => 'factura',
            '{{document_number}}' => '001-001-000000123',
            '{{access_key}}' => '1234567890123456789012345678901234567890123456789',
            '{{issue_date}}' => now()->format('d/m/Y'),
            '{{authorization_number}}' => '1234567890123456789012345678901234567890123456789',
            '{{authorization_date}}' => now()->format('d/m/Y H:i'),
            '{{total}}' => '112.00',
            '{{portal_url}}' => route('portal.login'),
        ];
    }

    /**
     * @param  array<string, string>  $placeholders
     * @return array<string, mixed>
     */
    public function compile(array $placeholders): array
    {
        return $this->compileFromData($this->all(), $placeholders);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $placeholders
     * @return array<string, mixed>
     */
    public function compileFromData(array $settings, array $placeholders): array
    {
        $settings = array_merge($this->all(), $settings);

        return [
            'subject' => $this->renderTextTemplate((string) $settings['subject_template'], $placeholders),
            'header_title' => $this->renderTextTemplate((string) $settings['header_title'], $placeholders),
            'header_subtitle' => $this->renderTextTemplate((string) $settings['header_subtitle'], $placeholders),
            'badge_text' => $this->renderTextTemplate((string) $settings['badge_text'], $placeholders),
            'accent_color' => (string) $settings['accent_color'],
            'body_html' => new HtmlString($this->renderHtmlTemplate((string) $settings['body_html'], $placeholders)),
            'footer_html' => new HtmlString($this->renderHtmlTemplate((string) $settings['footer_html'], $placeholders)),
            'cta_label' => $this->renderTextTemplate((string) $settings['cta_label'], $placeholders),
            'show_portal_button' => (bool) $settings['show_portal_button'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function availablePlaceholders(): array
    {
        return [
            '{{customer_name}}',
            '{{company_name}}',
            '{{company_ruc}}',
            '{{company_address}}',
            '{{document_type}}',
            '{{document_type_lower}}',
            '{{document_number}}',
            '{{access_key}}',
            '{{issue_date}}',
            '{{authorization_number}}',
            '{{authorization_date}}',
            '{{total}}',
            '{{portal_url}}',
        ];
    }

    private function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $value,
            'json' => $value ? json_decode($value, true) : null,
            default => $value,
        };
    }

    private function prepareForStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private function renderTextTemplate(string $template, array $placeholders): string
    {
        return trim(strtr($template, $placeholders));
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private function renderHtmlTemplate(string $template, array $placeholders): string
    {
        return strtr($template, $placeholders);
    }
}
