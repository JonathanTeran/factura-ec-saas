<?php

namespace Tests\Feature\Arbitros;

use App\Enums\DocumentType;
use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\SRI\ElectronicDocument;
use App\Models\SRI\SequentialNumber;
use App\Models\Tenant\Tenant;
use App\Services\Arbitros\RefereeInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/**
 * Facturación 1×1 del árbitro: secuenciales por el asignador atómico compartido
 * (no colisiona con el flujo normal) y sin doble-facturación por doble-submit.
 */
class RefereeInvoiceServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    private Championship $championship;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->tenant->update(['business_type' => Tenant::BUSINESS_TYPE_REFEREE]);
        Queue::fake();
        Carbon::setTestNow('2026-08-10'); // día 10, dentro de 1–20
        $this->championship = Championship::create(['name' => 'LIGA X', 'season' => '2026']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pendingMatch(float $fee = 30): OfficiatedMatch
    {
        return OfficiatedMatch::create([
            'tenant_id' => $this->tenant->id,
            'championship_id' => $this->championship->id,
            'home_club_id' => Club::create(['name' => 'A ' . uniqid()])->id,
            'away_club_id' => Club::create(['name' => 'B ' . uniqid()])->id,
            'match_date' => '2026-07-10', // mes anterior
            'role' => 'arbitro',
            'fee' => $fee,
            'status' => 'pending',
        ]);
    }

    public function test_uses_shared_atomic_sequential_and_advances_counter(): void
    {
        $service = app(RefereeInvoiceService::class);
        $fef = $this->createCustomer();

        $m1 = $this->pendingMatch();
        $m2 = $this->pendingMatch();

        $service->invoiceBatch($this->tenant, [$m1->id, $m2->id], $fef, $this->user, $this->emissionPoint);

        $docs = ElectronicDocument::withoutTenantScope()
            ->whereIn('id', [$m1->fresh()->electronic_document_id, $m2->fresh()->electronic_document_id])
            ->pluck('sequential')
            ->sort()
            ->values();

        // Secuenciales distintos y consecutivos.
        $this->assertCount(2, $docs->unique());
        $this->assertSame(['000000001', '000000002'], $docs->all());

        // El contador persistente avanzó (asignador atómico, no max()+1).
        $counter = SequentialNumber::where('emission_point_id', $this->emissionPoint->id)
            ->where('document_type', DocumentType::FACTURA->value)
            ->value('current_number');
        $this->assertSame(2, (int) $counter);
    }

    public function test_referee_invoice_does_not_collide_with_next_document_sequential(): void
    {
        $service = app(RefereeInvoiceService::class);
        $fef = $this->createCustomer();
        $m1 = $this->pendingMatch();

        $service->invoiceBatch($this->tenant, [$m1->id], $fef, $this->user, $this->emissionPoint);
        $refSeq = ElectronicDocument::withoutTenantScope()->find($m1->fresh()->electronic_document_id)->sequential;

        // Un documento posterior por el asignador atómico toma el SIGUIENTE número.
        $next = $this->emissionPoint->getNextSequential(DocumentType::FACTURA->value);

        $this->assertSame('000000001', $refSeq);
        $this->assertSame(2, $next); // no repite el 1
    }

    public function test_already_queued_match_is_skipped_no_second_document(): void
    {
        $service = app(RefereeInvoiceService::class);
        $fef = $this->createCustomer();
        $match = $this->pendingMatch();

        // Primera facturación.
        $service->invoiceBatch($this->tenant, [$match->id], $fef, $this->user, $this->emissionPoint);
        $this->assertNotSame('pending', $match->fresh()->status);
        $docCount = ElectronicDocument::withoutTenantScope()->count();

        // Segundo intento sobre el mismo partido (doble-submit): se salta.
        $results = $service->invoiceBatch($this->tenant, [$match->id], $fef, $this->user, $this->emissionPoint);

        $this->assertSame('skipped', $results[0]['status']);
        $this->assertSame($docCount, ElectronicDocument::withoutTenantScope()->count());
    }

    public function test_concept_and_zero_iva_on_the_line(): void
    {
        $service = app(RefereeInvoiceService::class);
        $fef = $this->createCustomer();
        $match = $this->pendingMatch(45);

        $service->invoiceBatch($this->tenant, [$match->id], $fef, $this->user, $this->emissionPoint);

        $doc = ElectronicDocument::withoutTenantScope()->find($match->fresh()->electronic_document_id);
        $item = $doc->items()->first();

        $this->assertStringContainsString('campeonato LIGA X', $item->description);
        $this->assertSame('0', (string) $item->tax_percentage_code);
        $this->assertEquals(45, (float) $item->unit_price);
        $this->assertSame($fef->id, $doc->customer_id);
    }
}
