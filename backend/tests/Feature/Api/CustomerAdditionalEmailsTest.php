<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CustomerAdditionalEmailsTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_can_create_customer_with_additional_emails(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'identification_type' => '05',
            'identification_number' => '0912345678',
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'additional_emails' => ['contabilidad@example.com', 'gerencia@example.com'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.additional_emails', [
                'contabilidad@example.com',
                'gerencia@example.com',
            ]);

        $customer = \App\Models\Tenant\Customer::where('identification', '0912345678')->first();
        $this->assertEquals(
            ['contabilidad@example.com', 'gerencia@example.com'],
            $customer->additional_emails
        );
    }

    public function test_can_update_customer_additional_emails(): void
    {
        $customer = $this->createCustomer();

        $response = $this->putJson("/api/v1/customers/{$customer->id}", [
            'identification_type' => $customer->identification_type->value,
            'identification_number' => $customer->identification,
            'name' => $customer->name,
            'additional_emails' => ['copia@example.com'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.additional_emails', ['copia@example.com']);

        $customer->refresh();
        $this->assertEquals(['copia@example.com'], $customer->additional_emails);
    }

    public function test_show_returns_additional_emails(): void
    {
        $customer = $this->createCustomer();
        $customer->update(['additional_emails' => ['cc1@example.com', 'cc2@example.com']]);

        $response = $this->getJson("/api/v1/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonPath('data.customer.additional_emails', [
                'cc1@example.com',
                'cc2@example.com',
            ]);
    }

    public function test_invalid_email_in_additional_emails_returns_422(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'identification_type' => '05',
            'identification_number' => '0912345679',
            'name' => 'Cliente Inválido',
            'additional_emails' => ['valido@example.com', 'no-es-un-email'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['additional_emails.1']);
    }

    public function test_more_than_five_additional_emails_returns_422(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'identification_type' => '05',
            'identification_number' => '0912345670',
            'name' => 'Cliente Excedido',
            'additional_emails' => [
                'a@example.com', 'b@example.com', 'c@example.com',
                'd@example.com', 'e@example.com', 'f@example.com',
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['additional_emails']);
    }
}
