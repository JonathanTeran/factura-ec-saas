<?php

namespace Tests\Feature\Api\Ext;

use App\Enums\DocumentStatus;
use App\Enums\IdentificationType;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;
use Tests\Traits\UsesApiKeys;

/** Flujo de documentos por la API de integración. */
class ExternalDocumentsApiTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase, UsesApiKeys;

    protected string $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->enableApiAccess();
        [, $this->key] = $this->makeApiKey();
        Queue::fake();
    }

    public function test_me_returns_tenant_plan_limits_and_key(): void
    {
        $this->extGet('/me', $this->key)
            ->assertOk()
            ->assertJsonPath('data.tenant.id', $this->tenant->id)
            ->assertJsonPath('data.plan.slug', 'negocio')
            ->assertJsonPath('data.api_key.scopes', ['*'])
            ->assertJsonStructure(['data' => [
                'tenant' => ['id', 'name'],
                'plan' => ['slug', 'name'],
                'limits' => ['documents_per_month', 'effective_document_limit', 'unlimited', 'rate_limit_per_minute'],
                'usage' => ['documents_this_period', 'period_start'],
                'api_key' => ['name', 'key_prefix', 'scopes', 'effective_rate_limit', 'expires_at'],
            ]]);
    }

    public function test_companies_lists_establishments_and_emission_points(): void
    {
        $this->extGet('/companies', $this->key)
            ->assertOk()
            ->assertJsonPath('data.companies.0.id', $this->company->id)
            ->assertJsonPath('data.companies.0.branches.0.id', $this->branch->id)
            ->assertJsonPath('data.companies.0.branches.0.emission_points.0.id', $this->emissionPoint->id)
            ->assertJsonPath('data.companies.0.branches.0.emission_points.0.series', $this->branch->code.'-'.$this->emissionPoint->code);
    }

    public function test_create_document_sends_it_to_the_sri_by_default(): void
    {
        $customer = $this->createCustomer();

        $response = $this->extPost('/documents', $this->key, $this->invoicePayload($customer->id));

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document.status', DocumentStatus::PROCESSING->value)
            ->assertJsonPath('data.document.document_type', '01');

        $this->assertNotEmpty($response->json('data.document.access_key'));
        Queue::assertPushed(ProcessDocumentJob::class, 1);
    }

    public function test_create_document_without_send_leaves_a_draft(): void
    {
        $customer = $this->createCustomer();

        $this->extPost('/documents', $this->key, $this->invoicePayload($customer->id, ['send' => false]))
            ->assertStatus(201)
            ->assertJsonPath('data.document.status', DocumentStatus::DRAFT->value);

        Queue::assertNothingPushed();
    }

    public function test_idempotency_key_replays_the_same_response(): void
    {
        $customer = $this->createCustomer();
        $payload = $this->invoicePayload($customer->id);
        $headers = ['Idempotency-Key' => 'pedido-1001'];

        $first = $this->extPost('/documents', $this->key, $payload, $headers)->assertStatus(201);
        $second = $this->extPost('/documents', $this->key, $payload, $headers)->assertStatus(201);

        $this->assertSame($first->json('data.document.id'), $second->json('data.document.id'));
        $this->assertSame('true', $second->headers->get('Idempotent-Replayed'));
        $this->assertNull($first->headers->get('Idempotent-Replayed'));
        $this->assertSame(1, ElectronicDocument::withoutGlobalScopes()->count());
        Queue::assertPushed(ProcessDocumentJob::class, 1);
    }

    public function test_idempotency_key_with_a_different_body_conflicts(): void
    {
        $customer = $this->createCustomer();
        $headers = ['Idempotency-Key' => 'pedido-1002'];

        $this->extPost('/documents', $this->key, $this->invoicePayload($customer->id), $headers)->assertStatus(201);

        $this->extPost('/documents', $this->key, $this->invoicePayload($customer->id, ['total' => 230.00]), $headers)
            ->assertStatus(409)
            ->assertJsonPath('error', 'idempotency_key_reused');

        $this->assertSame(1, ElectronicDocument::withoutGlobalScopes()->count());
    }

    public function test_validation_errors_have_a_stable_shape(): void
    {
        $this->extPost('/documents', $this->key, ['document_type' => '01'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonValidationErrors(['company_id', 'customer_id', 'emission_point_id', 'total', 'items']);
    }

    public function test_sri_prevalidation_failure_returns_422_with_the_document(): void
    {
        $consumidorFinal = $this->createCustomer([
            'identification_type' => IdentificationType::CONSUMIDOR_FINAL,
            'identification' => '9999999999999',
            'name' => 'CONSUMIDOR FINAL',
        ]);

        $response = $this->extPost('/documents', $this->key, $this->invoicePayload($consumidorFinal->id));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonPath('data.document.status', DocumentStatus::REJECTED->value);

        $this->assertNotEmpty($response->json('errors.sri'));
        Queue::assertNothingPushed();
    }

    public function test_plan_limit_reached_returns_403(): void
    {
        $this->plan->update(['max_documents_per_month' => 1]);
        $this->tenant->syncPlanLimits($this->plan->fresh());
        $this->createDocument();
        $customer = $this->createCustomer();

        $this->extPost('/documents', $this->key, $this->invoicePayload($customer->id))
            ->assertStatus(403)
            ->assertJsonPath('error', 'plan_limit_reached');
    }

    public function test_list_supports_filters_and_caps_per_page(): void
    {
        $this->createDocument(['access_key' => str_repeat('1', 49)]);
        $this->createDocument(['access_key' => str_repeat('2', 49)]);

        $this->extGet('/documents?per_page=500', $this->key)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(2, 'data');

        $this->extGet('/documents?access_key='.str_repeat('2', 49), $this->key)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.access_key', str_repeat('2', 49));

        $this->extGet('/documents?status=authorized', $this->key)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_show_and_status(): void
    {
        $document = $this->createDocument();

        $this->extGet('/documents/'.$document->id, $this->key)
            ->assertOk()
            ->assertJsonPath('data.document.id', $document->id)
            ->assertJsonStructure(['data' => ['document' => ['items', 'customer', 'company']]]);

        $this->extGet('/documents/'.$document->id.'/status', $this->key)
            ->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::DRAFT->value);
    }

    public function test_documents_of_other_tenants_are_404(): void
    {
        ['tenant' => $other] = $this->createSecondTenant();
        $foreign = ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $other->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        $this->extGet('/documents/'.$foreign->id, $this->key)->assertStatus(404)->assertJsonPath('error', 'not_found');
        $this->extGet('/documents/999999', $this->key)->assertStatus(404)->assertJsonPath('error', 'not_found');
    }

    public function test_send_a_draft(): void
    {
        $document = $this->createDocument();

        $this->extPost('/documents/'.$document->id.'/send', $this->key)
            ->assertOk()
            ->assertJsonPath('data.document.status', DocumentStatus::PROCESSING->value);

        Queue::assertPushed(ProcessDocumentJob::class, 1);
    }

    public function test_void_persists_the_reason(): void
    {
        $document = $this->createDocument(['status' => DocumentStatus::AUTHORIZED]);

        $this->extPost('/documents/'.$document->id.'/void', $this->key, ['reason' => 'Error en el precio'])
            ->assertOk()
            ->assertJsonPath('data.document.status', DocumentStatus::VOIDED->value);

        $document->refresh();
        $this->assertSame('Error en el precio', $document->void_reason);
        $this->assertNotNull($document->voided_at);
    }

    public function test_email_requires_an_authorized_document(): void
    {
        $document = $this->createDocument();

        $this->extPost('/documents/'.$document->id.'/email', $this->key, ['email' => 'cliente@example.com'])
            ->assertStatus(400);
    }

    public function test_ride_streams_a_pdf_or_returns_a_temporary_url(): void
    {
        $document = $this->createDocument();

        $pdf = $this->get('/api/v1/ext/documents/'.$document->id.'/ride', $this->extHeaders($this->key));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->extGet('/documents/'.$document->id.'/ride?url=1', $this->key)
            ->assertOk()
            ->assertJson(fn ($json) => $json->where('data.url', fn ($url) => str_contains($url, '/api/v1/public/documents/'))->etc());
    }

    public function test_xml_not_available_is_404(): void
    {
        $document = $this->createDocument();

        $this->extGet('/documents/'.$document->id.'/xml', $this->key)
            ->assertStatus(404)
            ->assertJsonPath('error', 'xml_not_available');
    }
}
