<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class RetentionApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function retentionPayload(array $overrides = []): array
    {
        $customer = $this->createCustomer();

        return array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '07',
            'issue_date' => '2026-06-15',
            'subtotal_no_tax' => 0,
            'subtotal_0' => 0,
            'subtotal_5' => 0,
            'subtotal_12' => 0,
            'subtotal_15' => 0,
            'total_tax' => 0,
            'total' => 25.00,
            'withholding_details' => [
                [
                    'support_doc_code' => '01',
                    'support_doc_number' => '001-001-000000123',
                    'support_doc_date' => '2026-06-10',
                    'support_doc_total' => 115.00,
                    'support_reason_code' => '01',
                    'tax_type' => 'renta',
                    'retention_code' => '303',
                    'tax_base' => 100.00,
                    'retention_rate' => 10,
                    'retained_value' => 10.00,
                ],
                [
                    'support_doc_code' => '01',
                    'support_doc_number' => '001-001-000000123',
                    'support_doc_date' => '2026-06-10',
                    'support_doc_total' => 115.00,
                    'support_reason_code' => '01',
                    'tax_type' => 'iva',
                    'retention_code' => '9',
                    'tax_base' => 15.00,
                    'retention_rate' => 100,
                    'retained_value' => 15.00,
                ],
            ],
        ], $overrides);
    }

    public function test_can_create_retention_with_withholding_details(): void
    {
        $response = $this->postJson('/api/v1/documents', $this->retentionPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document.document_type', '07')
            ->assertJsonCount(2, 'data.document.withholding_details');

        $documentId = $response->json('data.document.id');

        $this->assertDatabaseHas('electronic_documents', [
            'id' => $documentId,
            'tenant_id' => $this->tenant->id,
            'document_type' => '07',
            'status' => DocumentStatus::DRAFT->value,
        ]);

        $this->assertDatabaseHas('withholding_details', [
            'tenant_id' => $this->tenant->id,
            'electronic_document_id' => $documentId,
            'tax_type' => 'renta',
            'retention_code' => '303',
            'tax_base' => 100.00,
            'retention_rate' => 10.00,
            'retained_value' => 10.00,
            'support_doc_code' => '01',
            'support_doc_number' => '001-001-000000123',
        ]);

        $this->assertDatabaseHas('withholding_details', [
            'tenant_id' => $this->tenant->id,
            'electronic_document_id' => $documentId,
            'tax_type' => 'iva',
            'retention_code' => '9',
            'tax_base' => 15.00,
            'retention_rate' => 100.00,
            'retained_value' => 15.00,
        ]);

        $this->assertDatabaseCount('withholding_details', 2);
    }

    public function test_retention_detail_endpoint_returns_withholding_details(): void
    {
        $createResponse = $this->postJson('/api/v1/documents', $this->retentionPayload());
        $createResponse->assertCreated();

        $documentId = $createResponse->json('data.document.id');

        $response = $this->getJson("/api/v1/documents/{$documentId}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.document.withholding_details')
            ->assertJsonPath('data.document.withholding_details.0.tax_type', 'renta')
            ->assertJsonPath('data.document.withholding_details.0.retention_code', '303')
            ->assertJsonPath('data.document.withholding_details.0.tax_base', 100)
            ->assertJsonPath('data.document.withholding_details.0.retained_value', 10)
            ->assertJsonPath('data.document.withholding_details.0.support_doc_number', '001-001-000000123')
            ->assertJsonPath('data.document.withholding_details.0.support_doc_date', '2026-06-10')
            ->assertJsonPath('data.document.withholding_details.1.tax_type', 'iva')
            ->assertJsonPath('data.document.withholding_details.1.retained_value', 15);
    }

    public function test_retention_requires_retention_code(): void
    {
        $payload = $this->retentionPayload();
        unset($payload['withholding_details'][0]['retention_code']);

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['withholding_details.0.retention_code']);
    }

    public function test_retention_requires_withholding_details(): void
    {
        $payload = $this->retentionPayload();
        unset($payload['withholding_details']);

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['withholding_details']);
    }
}
