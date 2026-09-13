<?php

namespace Tests\Feature\Api;

use App\Models\SRI\SequentialNumber;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmissionPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/** Secuenciales por punto de emisión: ver el siguiente número y ajustarlo sin retroceder. */
class EmissionPointSequentialsApiTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function url(): string
    {
        return "/api/v1/branches/{$this->branch->id}/emission-points/{$this->emissionPoint->id}/sequentials";
    }

    public function test_lists_next_sequential_per_document_type(): void
    {
        SequentialNumber::create([
            'tenant_id' => $this->tenant->id,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'current_number' => 14,
        ]);
        $this->createDocument(['document_type' => '01', 'sequential' => '000000014']);

        $response = $this->getJson($this->url());

        $response->assertOk()
            ->assertJsonCount(6, 'data.sequentials')
            ->assertJsonPath('data.emission_point.id', $this->emissionPoint->id);

        $invoice = collect($response->json('data.sequentials'))->firstWhere('document_type', '01');
        $this->assertSame(14, $invoice['current_number']);
        $this->assertSame(15, $invoice['next_number']);
        $this->assertStringEndsWith('-000000015', $invoice['next_formatted']);
        $this->assertSame(14, $invoice['last_issued']);
        $this->assertSame(1, $invoice['documents_count']);

        $creditNote = collect($response->json('data.sequentials'))->firstWhere('document_type', '04');
        $this->assertSame(1, $creditNote['next_number']);
        $this->assertSame(0, $creditNote['last_issued']);
    }

    public function test_update_sets_last_number_and_next_document_continues(): void
    {
        $this->putJson($this->url(), [
            'sequentials' => [['document_type' => '01', 'last_number' => 120]],
        ])->assertOk()
            ->assertJsonPath('data.sequentials.0.document_type', '01')
            ->assertJsonPath('data.sequentials.0.next_number', 121);

        $this->postJson('/api/v1/documents', [
            'company_id' => $this->company->id,
            'customer_id' => $this->createCustomer()->id,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'subtotal_15' => 10,
            'total_tax' => 1.5,
            'total' => 11.5,
            'payment_method' => '01',
            'items' => [[
                'main_code' => 'X', 'description' => 'x', 'quantity' => 1, 'unit_price' => 10,
                'subtotal' => 10, 'tax_percentage_code' => '4', 'tax_rate' => 15, 'tax_base' => 10, 'tax_value' => 1.5,
            ]],
        ])->assertCreated()->assertJsonPath('data.document.sequential', '000000121');
    }

    public function test_update_cannot_go_below_last_issued(): void
    {
        $this->createDocument(['document_type' => '01', 'sequential' => '000000050']);

        $this->putJson($this->url(), [
            'sequentials' => [['document_type' => '01', 'last_number' => 10]],
        ])->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'retroceder') && str_contains($m, '000000050'));

        // Igual al último emitido sí se acepta (siguiente = 51).
        $this->putJson($this->url(), [
            'sequentials' => [['document_type' => '01', 'last_number' => 50]],
        ])->assertOk()->assertJsonPath('data.sequentials.0.next_number', 51);
    }

    public function test_branches_endpoint_exposes_next_invoice_number(): void
    {
        SequentialNumber::create([
            'tenant_id' => $this->tenant->id,
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'current_number' => 7,
        ]);

        $response = $this->getJson("/api/v1/companies/{$this->company->id}/branches")->assertOk();
        $ep = collect($response->json('data.branches'))->firstWhere('id', $this->branch->id)['emission_points'][0];

        $this->assertStringEndsWith('-000000008', $ep['next_invoice_number']);
        $this->assertSame(8, collect($ep['sequentials'])->firstWhere('document_type', '01')['next_number']);
    }

    public function test_sequentials_scoped_to_tenant(): void
    {
        $other = $this->createSecondTenant();
        $branch = Branch::factory()->create(['tenant_id' => $other['tenant']->id]);
        $ep = EmissionPoint::factory()->create(['tenant_id' => $other['tenant']->id, 'branch_id' => $branch->id]);

        // El scope global por tenant oculta el establecimiento ajeno: 404.
        $this->getJson("/api/v1/branches/{$branch->id}/emission-points/{$ep->id}/sequentials")
            ->assertStatus(404);
    }
}
