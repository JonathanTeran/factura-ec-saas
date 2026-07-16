<?php

namespace Tests\Feature\Arbitros;

use App\Enums\DocumentStatus;
use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/**
 * Anular una factura debe REACTIVAR el partido (§4.5): vuelve a pendiente, se
 * desvincula del documento y queda re-facturable. Vía el observer
 * (autorizada→anulada, rechazada) y vía el endpoint referee (incluye borradores
 * atascados).
 */
class RefereeReactivateTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->tenant->update(['business_type' => Tenant::BUSINESS_TYPE_REFEREE]);
    }

    private function officiatedWithDocument(string $docStatus, string $matchStatus): OfficiatedMatch
    {
        $ch = Championship::create(['name' => 'LIGA X', 'season' => '2026']);
        $home = Club::create(['name' => 'A']);
        $away = Club::create(['name' => 'B']);
        $customer = $this->createCustomer();

        $doc = ElectronicDocument::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            'document_type' => '01',
            'environment' => '1',
            'series' => '001-001',
            'sequential' => '000000001',
            'status' => $docStatus,
            'issue_date' => now()->toDateString(),
        ]);

        return OfficiatedMatch::create([
            'tenant_id' => $this->tenant->id,
            'championship_id' => $ch->id,
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'match_date' => '2026-07-10',
            'role' => 'arbitro',
            'fee' => 30,
            'status' => $matchStatus,
            'electronic_document_id' => $doc->id,
            'invoiced_at' => now(),
        ]);
    }

    public function test_voiding_authorized_invoice_reactivates_match_via_observer(): void
    {
        $match = $this->officiatedWithDocument(DocumentStatus::AUTHORIZED->value, 'invoiced');

        $match->document->update(['status' => DocumentStatus::VOIDED, 'voided_at' => now()]);

        $match->refresh();
        $this->assertSame('pending', $match->status);
        $this->assertNull($match->electronic_document_id);
        $this->assertNull($match->invoiced_at);
    }

    public function test_rejected_invoice_reactivates_match(): void
    {
        $match = $this->officiatedWithDocument(DocumentStatus::PROCESSING->value, 'queued');

        $match->document->update(['status' => DocumentStatus::REJECTED]);

        $this->assertSame('pending', $match->fresh()->status);
    }

    public function test_reactivate_endpoint_frees_stuck_draft(): void
    {
        $match = $this->officiatedWithDocument(DocumentStatus::DRAFT->value, 'queued');

        $this->postJson("/api/v1/referee/matches/{$match->id}/reactivate")->assertOk();

        $match->refresh();
        $this->assertSame('pending', $match->status);
        $this->assertNull($match->electronic_document_id);
    }

    public function test_reactivate_endpoint_voids_authorized_invoice(): void
    {
        $match = $this->officiatedWithDocument(DocumentStatus::AUTHORIZED->value, 'invoiced');
        $docId = $match->electronic_document_id;

        $this->postJson("/api/v1/referee/matches/{$match->id}/reactivate")->assertOk();

        $this->assertSame('pending', $match->fresh()->status);
        $this->assertSame(
            DocumentStatus::VOIDED,
            ElectronicDocument::withoutTenantScope()->find($docId)->status
        );
    }

    public function test_reactivate_rejects_a_pending_match(): void
    {
        $ch = Championship::create(['name' => 'LIGA X', 'season' => '2026']);
        $match = OfficiatedMatch::create([
            'tenant_id' => $this->tenant->id,
            'championship_id' => $ch->id,
            'match_date' => '2026-07-10',
            'role' => 'arbitro',
            'fee' => 30,
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/referee/matches/{$match->id}/reactivate")->assertStatus(400);
    }
}
