<?php

namespace Tests\Feature;

use App\Enums\JournalEntrySource;
use App\Models\Accounting\JournalEntry;
use App\Models\Tenant\PosSession;
use App\Models\Tenant\PosTransaction;
use App\Models\SRI\ElectronicDocument;
use App\Services\Accounting\AutoJournalEntryService;
use App\Services\Accounting\BasicAccountingActivator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class CashCycleAccountingTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        app(BasicAccountingActivator::class)->activate($this->company);
    }

    private function makePosTransaction(array $overrides = []): PosTransaction
    {
        $session = PosSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
        ]);

        return PosTransaction::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'pos_session_id' => $session->id,
            'electronic_document_id' => null,
            'subtotal' => 100.00,
            'tax' => 15.00,
            'discount' => 0,
            'total' => 115.00,
            'status' => 'completed',
        ], $overrides));
    }

    public function test_pos_sale_without_invoice_generates_cash_sale_entry(): void
    {
        $tx = $this->makePosTransaction();

        $entry = app(AutoJournalEntryService::class)
            ->forCompany($this->company)
            ->generateFromPosTransaction($tx);

        $this->assertNotNull($entry);
        $this->assertEquals(JournalEntrySource::AUTO_POS, $entry->source_type);
        $this->assertEquals('posted', $entry->status->value ?? $entry->status);

        $lines = $entry->lines()->with('account')->get();
        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');
        $this->assertEquals(115.00, (float) $totalDebit);
        $this->assertEquals((float) $totalDebit, (float) $totalCredit);

        // Caja al debe, ventas e IVA al haber
        $this->assertEquals(115.00, (float) $lines->firstWhere('account.code', '1.01.01.01')?->debit);
        $this->assertEquals(100.00, (float) $lines->firstWhere('account.code', '4.01.01')?->credit);
        $this->assertEquals(15.00, (float) $lines->firstWhere('account.code', '2.01.07.01')?->credit);
    }

    public function test_pos_entry_generation_is_idempotent(): void
    {
        $tx = $this->makePosTransaction();
        $service = app(AutoJournalEntryService::class)->forCompany($this->company);

        $first = $service->generateFromPosTransaction($tx);
        $second = $service->generateFromPosTransaction($tx);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, JournalEntry::where('source_document_id', $tx->id)
            ->where('source_document_type', PosTransaction::class)->count());
    }

    private function makeInvoice(float $subtotal, float $tax, float $total): ElectronicDocument
    {
        return ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'subtotal_0' => 0,
            'subtotal_5' => 0,
            'subtotal_12' => 0,
            'subtotal_15' => $subtotal,
            'subtotal_no_tax' => 0,
            'total_tax' => $tax,
            'total' => $total,
        ]);
    }

    public function test_authorized_invoice_generates_balanced_sales_entry(): void
    {
        $document = $this->makeInvoice(100.00, 15.00, 115.00);

        $entry = app(AutoJournalEntryService::class)
            ->forCompany($this->company)
            ->generateFromDocument($document);

        $this->assertNotNull($entry, 'El asiento de la factura no se generó (¿subtotal en 0?)');

        $lines = $entry->lines()->with('account')->get();
        $this->assertEquals(115.00, (float) $lines->firstWhere('account.code', '1.01.02.05')?->debit);
        $this->assertEquals(100.00, (float) $lines->firstWhere('account.code', '4.01.01')?->credit);
        $this->assertEquals(15.00, (float) $lines->firstWhere('account.code', '2.01.07.01')?->credit);
        $this->assertEquals((float) $lines->sum('debit'), (float) $lines->sum('credit'));
    }

    public function test_pos_sale_with_invoice_generates_collection_entry(): void
    {
        $document = $this->makeInvoice(100.00, 15.00, 115.00);

        $tx = $this->makePosTransaction(['electronic_document_id' => $document->id]);

        $entry = app(AutoJournalEntryService::class)
            ->forCompany($this->company)
            ->generateFromPosTransaction($tx);

        $this->assertNotNull($entry);
        $lines = $entry->lines()->with('account')->get();

        // La factura ya registró la venta: aquí solo se cobra (Caja vs CxC)
        $this->assertEquals(115.00, (float) $lines->firstWhere('account.code', '1.01.01.01')?->debit);
        $this->assertEquals(115.00, (float) $lines->firstWhere('account.code', '1.01.02.05')?->credit);
        $this->assertNull($lines->firstWhere('account.code', '4.01.01'));
    }

    public function test_pos_service_transaction_dispatches_accounting_entry(): void
    {
        $this->tenant->update(['has_pos' => true]);
        $this->tenant->refresh();

        $session = PosSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'status' => 'open',
        ]);

        $tx = app(\App\Services\Pos\PosService::class)
            ->forTenant($this->tenant)
            ->createTransaction($session, ['payment_method' => 'cash'], [
                ['description' => 'Producto suelto', 'quantity' => 2, 'unit_price' => 50, 'tax_rate' => 15],
            ]);

        $entry = JournalEntry::where('source_document_type', PosTransaction::class)
            ->where('source_document_id', $tx->id)
            ->first();

        $this->assertNotNull($entry, 'La venta POS no generó asiento automático');
        $this->assertEquals(JournalEntrySource::AUTO_POS, $entry->source_type);
    }

    public function test_registering_invoice_payment_creates_collection_entry(): void
    {
        $document = $this->makeInvoice(200.00, 30.00, 230.00);

        $response = $this->postJson("/api/v1/documents/{$document->id}/payments", [
            'amount' => 230.00,
            'payment_method' => 'transfer',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment.amount', '230.00')
            ->assertJsonPath('data.document.paid_amount', '230.00')
            ->assertJsonPath('data.document.balance', '0.00');

        $entry = JournalEntry::where('source_type', JournalEntrySource::AUTO_PAYMENT)->first();
        $this->assertNotNull($entry);

        $lines = $entry->lines()->with('account')->get();
        // Transferencia entra a Bancos; se descarga CxC
        $this->assertEquals(230.00, (float) $lines->firstWhere('account.code', '1.01.01.03')?->debit);
        $this->assertEquals(230.00, (float) $lines->firstWhere('account.code', '1.01.02.05')?->credit);
    }

    public function test_cash_payment_debits_caja(): void
    {
        $document = $this->makeInvoice(43.48, 6.52, 50.00);

        $this->postJson("/api/v1/documents/{$document->id}/payments", [
            'amount' => 50.00,
            'payment_method' => 'cash',
        ])->assertCreated();

        $entry = JournalEntry::where('source_type', JournalEntrySource::AUTO_PAYMENT)->first();
        $lines = $entry->lines()->with('account')->get();
        $this->assertEquals(50.00, (float) $lines->firstWhere('account.code', '1.01.01.01')?->debit);
    }

    public function test_rejects_payment_exceeding_balance(): void
    {
        $document = $this->makeInvoice(86.96, 13.04, 100.00);

        $this->postJson("/api/v1/documents/{$document->id}/payments", [
            'amount' => 60.00,
            'payment_method' => 'cash',
        ])->assertCreated();

        $this->postJson("/api/v1/documents/{$document->id}/payments", [
            'amount' => 50.00,
            'payment_method' => 'cash',
        ])->assertStatus(422);
    }
}
