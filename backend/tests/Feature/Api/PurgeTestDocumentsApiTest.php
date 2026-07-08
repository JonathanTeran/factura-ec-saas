<?php

namespace Tests\Feature\Api;

use App\Models\SRI\ElectronicDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class PurgeTestDocumentsApiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Sanctum::actingAs($this->user);
    }

    public function test_purges_only_test_environment_documents(): void
    {
        $testDoc = $this->createDocument(['environment' => '1']);
        $prodDoc = $this->createDocument(['environment' => '2']);

        $response = $this->deleteJson(
            "/api/v1/companies/{$this->company->id}/test-documents",
            ['confirm' => true],
        );

        $response->assertOk()->assertJsonPath('data.deleted', 1);

        $this->assertDatabaseMissing('electronic_documents', ['id' => $testDoc->id]);
        $this->assertDatabaseHas('electronic_documents', ['id' => $prodDoc->id]);
        // Items del doc de pruebas cascadean.
        $this->assertDatabaseMissing('document_items', [
            'electronic_document_id' => $testDoc->id,
        ]);
    }

    public function test_requires_confirmation(): void
    {
        $this->createDocument(['environment' => '1']);

        $this->deleteJson("/api/v1/companies/{$this->company->id}/test-documents", [])
            ->assertStatus(422);

        $this->assertSame(1, ElectronicDocument::withoutGlobalScopes()->count());
    }

    public function test_cannot_purge_other_tenants_company(): void
    {
        ['tenant' => $tenant2] = $this->createSecondTenant();
        $company2 = \App\Models\Tenant\Company::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        // El global scope de tenant hace que la empresa ajena "no exista" (404).
        $this->deleteJson("/api/v1/companies/{$company2->id}/test-documents", ['confirm' => true])
            ->assertStatus(404);
    }
}
