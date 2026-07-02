<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class DocumentSettingsApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_get_returns_defaults_for_fresh_company(): void
    {
        $response = $this->getJson('/api/v1/document-settings');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.auto_send_email', true)
            ->assertJsonPath('data.email_subject', 'Su comprobante electrónico')
            ->assertJsonPath('data.email_message', 'Adjunto encontrará su comprobante electrónico autorizado por el SRI.')
            ->assertJsonPath('data.ride_footer', '');
    }

    public function test_put_saves_and_get_reflects_it(): void
    {
        $payload = [
            'auto_send_email' => false,
            'email_subject' => 'Tu factura está lista',
            'email_message' => 'Gracias por tu compra. Adjunto tu comprobante.',
            'ride_footer' => 'Gracias por su preferencia.',
        ];

        $this->putJson('/api/v1/document-settings', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.auto_send_email', false)
            ->assertJsonPath('data.email_subject', 'Tu factura está lista')
            ->assertJsonPath('data.ride_footer', 'Gracias por su preferencia.');

        $this->getJson('/api/v1/document-settings')
            ->assertOk()
            ->assertJsonPath('data.auto_send_email', false)
            ->assertJsonPath('data.email_subject', 'Tu factura está lista')
            ->assertJsonPath('data.email_message', 'Gracias por tu compra. Adjunto tu comprobante.')
            ->assertJsonPath('data.ride_footer', 'Gracias por su preferencia.');
    }

    public function test_put_does_not_clobber_other_settings_keys(): void
    {
        $this->company->settings = ['onboarding_completed' => true];
        $this->company->save();

        $this->putJson('/api/v1/document-settings', [
            'auto_send_email' => true,
            'email_subject' => 'Asunto',
            'email_message' => 'Mensaje del correo.',
            'ride_footer' => 'Pie de página',
        ])->assertOk();

        $this->assertTrue($this->company->fresh()->settings['onboarding_completed']);
        $this->assertSame('Asunto', $this->company->fresh()->settings['documents']['email_subject']);
    }

    public function test_validation_rejects_missing_email_subject(): void
    {
        $this->putJson('/api/v1/document-settings', [
            'auto_send_email' => true,
            'email_message' => 'Mensaje del correo.',
            'ride_footer' => 'Pie',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email_subject');
    }
}
