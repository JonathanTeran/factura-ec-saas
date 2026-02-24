<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentStatus;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public function test_can_list_documents(): void
    {
        $this->createDocument();
        $this->createDocument();

        $response = $this->getJson('/api/v1/documents');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_invoice(): void
    {
        $customer = $this->createCustomer();

        $response = $this->postJson('/api/v1/documents', [
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'subtotal_12' => 100.00,
            'total_tax' => 12.00,
            'total' => 112.00,
            'payment_method' => '01',
            'items' => [
                [
                    'main_code' => 'PROD001',
                    'description' => 'Servicio de consultoría',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'discount' => 0,
                    'subtotal' => 100.00,
                    'tax_code' => '2',
                    'tax_percentage_code' => '2',
                    'tax_rate' => 12,
                    'tax_base' => 100.00,
                    'tax_value' => 12.00,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('electronic_documents', [
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'document_type' => '01',
            'status' => DocumentStatus::DRAFT->value,
        ]);
    }

    public function test_create_document_requires_subscription(): void
    {
        $this->subscription->delete();

        $customer = $this->createCustomer();

        $response = $this->postJson('/api/v1/documents', [
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'total' => 112.00,
            'items' => [
                [
                    'main_code' => 'PROD001',
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'subtotal' => 100,
                    'tax_base' => 100,
                    'tax_value' => 12,
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_create_document_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/documents', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id', 'customer_id', 'emission_point_id', 'document_type', 'total', 'items']);
    }

    public function test_can_show_document(): void
    {
        $document = $this->createDocument();

        $response = $this->getJson("/api/v1/documents/{$document->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_can_send_document_to_sri(): void
    {
        Queue::fake();

        // Company needs SRI password for sending
        $this->company->setSriPassword('test123');
        $this->company->save();

        $document = $this->createDocument();

        $response = $this->postJson("/api/v1/documents/{$document->id}/send");

        $response->assertOk();

        $document->refresh();
        $this->assertEquals(DocumentStatus::PROCESSING, $document->status);

        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_can_check_document_status(): void
    {
        $document = $this->createDocument(['status' => DocumentStatus::AUTHORIZED]);

        $response = $this->getJson("/api/v1/documents/{$document->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.status', 'authorized');
    }

    public function test_document_scoped_to_tenant(): void
    {
        $document = $this->createDocument();

        // Create second tenant and auth as them
        $second = $this->createSecondTenant();
        Sanctum::actingAs($second['user']);
        config(['app.tenant_id' => $second['tenant']->id]);

        $response = $this->getJson("/api/v1/documents/{$document->id}");

        // Global scope makes the document invisible (404), not forbidden (403)
        $response->assertNotFound();
    }
}
