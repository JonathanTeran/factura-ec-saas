<?php

namespace Tests\Unit\Services;

use App\Services\Settings\DocumentEmailTemplateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentEmailTemplateSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_default_template_settings(): void
    {
        $settings = app(DocumentEmailTemplateSettings::class)->all();

        $this->assertSame('{{document_type}} #{{document_number}} - {{company_name}}', $settings['subject_template']);
        $this->assertSame('#0284c7', $settings['accent_color']);
        $this->assertTrue($settings['show_portal_button']);
    }

    public function test_it_persists_and_compiles_template_settings(): void
    {
        $service = app(DocumentEmailTemplateSettings::class);

        $service->save([
            'subject_template' => 'Comprobante {{document_number}}',
            'header_title' => 'Mi Marca {{company_name}}',
            'header_subtitle' => 'RUC {{company_ruc}}',
            'badge_text' => 'Autorizado',
            'accent_color' => '#111111',
            'body_html' => '<p>Hola {{customer_name}}</p><p>Total {{total}}</p>',
            'footer_html' => '<p>{{company_name}}</p>',
            'cta_label' => 'Ver portal',
            'show_portal_button' => false,
        ]);

        $compiled = $service->compile([
            '{{customer_name}}' => 'Cliente Demo',
            '{{company_name}}' => 'AmePhia',
            '{{company_ruc}}' => '1790012345001',
            '{{document_type}}' => 'Factura',
            '{{document_type_lower}}' => 'factura',
            '{{document_number}}' => '001-001-000000123',
            '{{access_key}}' => 'abc',
            '{{issue_date}}' => '03/04/2026',
            '{{authorization_number}}' => 'abc',
            '{{authorization_date}}' => '03/04/2026 10:00',
            '{{total}}' => '112.00',
            '{{company_address}}' => 'Quito',
            '{{portal_url}}' => 'https://example.com/portal',
        ]);

        $this->assertSame('Comprobante 001-001-000000123', $compiled['subject']);
        $this->assertSame('Mi Marca AmePhia', $compiled['header_title']);
        $this->assertStringContainsString('Cliente Demo', $compiled['body_html']->toHtml());
        $this->assertFalse($compiled['show_portal_button']);
    }
}
