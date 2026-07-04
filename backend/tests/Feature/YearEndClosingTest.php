<?php

namespace Tests\Feature;

use App\Enums\JournalEntrySource;
use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\JournalEntry;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\BasicAccountingActivator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class YearEndClosingTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        app(BasicAccountingActivator::class)->activate($this->company);
        $this->user->update(['current_company_id' => $this->company->id]);
    }

    private function account(string $code): Account
    {
        return Account::where('company_id', $this->company->id)->where('code', $code)->firstOrFail();
    }

    private function postEntry(array $lines): JournalEntry
    {
        $service = app(AccountingService::class)->forCompany($this->company);
        $entry = $service->createJournalEntry([
            'entry_date' => now()->toDateString(),
            'description' => 'Movimiento de prueba',
            'source_type' => JournalEntrySource::MANUAL,
        ], $lines);
        $service->postJournalEntry($entry);

        return $entry;
    }

    private function annualPeriod(): FiscalPeriod
    {
        return FiscalPeriod::where('company_id', $this->company->id)
            ->where('year', now()->year)
            ->where('period_type', 'annual')
            ->firstOrFail();
    }

    public function test_closing_annual_period_generates_closing_entry_with_profit(): void
    {
        // Ingreso: venta 100 (CxC 115 / Ventas 100 + IVA 15)
        $this->postEntry([
            ['account_id' => $this->account('1.01.02.05')->id, 'debit' => 115, 'credit' => 0],
            ['account_id' => $this->account('4.01.01')->id, 'debit' => 0, 'credit' => 100],
            ['account_id' => $this->account('2.01.07.01')->id, 'debit' => 0, 'credit' => 15],
        ]);
        // Gasto: 40 (Gasto 40 / Caja 40)
        $this->postEntry([
            ['account_id' => $this->account('6.02.01')->id, 'debit' => 40, 'credit' => 0],
            ['account_id' => $this->account('1.01.01.01')->id, 'debit' => 0, 'credit' => 40],
        ]);

        $period = $this->annualPeriod();

        $this->postJson("/api/v1/accounting/fiscal-periods/{$period->id}/close")
            ->assertOk();

        $closing = JournalEntry::where('source_type', JournalEntrySource::CLOSING)->first();
        $this->assertNotNull($closing, 'No se generó el asiento de cierre');
        $this->assertEquals('posted', $closing->status->value ?? $closing->status);

        $lines = $closing->lines()->with('account')->get();
        // Se cierran ingresos (débito) y gastos (crédito); utilidad 60 al patrimonio
        $this->assertEquals(100.0, (float) $lines->firstWhere('account.code', '4.01.01')?->debit);
        $this->assertEquals(40.0, (float) $lines->firstWhere('account.code', '6.02.01')?->credit);
        $this->assertEquals(60.0, (float) $lines->firstWhere('account.code', '3.07.01')?->credit);
        $this->assertEquals((float) $lines->sum('debit'), (float) $lines->sum('credit'));

        $period->refresh();
        $this->assertEquals('closed', $period->status->value ?? $period->status);
    }

    public function test_closing_with_loss_debits_perdida_account(): void
    {
        // Solo gastos: pérdida de 30
        $this->postEntry([
            ['account_id' => $this->account('6.02.01')->id, 'debit' => 30, 'credit' => 0],
            ['account_id' => $this->account('1.01.01.01')->id, 'debit' => 0, 'credit' => 30],
        ]);

        $this->postJson("/api/v1/accounting/fiscal-periods/{$this->annualPeriod()->id}/close")
            ->assertOk();

        $closing = JournalEntry::where('source_type', JournalEntrySource::CLOSING)->first();
        $lines = $closing->lines()->with('account')->get();
        $this->assertEquals(30.0, (float) $lines->firstWhere('account.code', '3.07.02')?->debit);
    }

    public function test_closing_monthly_period_does_not_generate_closing_entry(): void
    {
        $monthly = FiscalPeriod::where('company_id', $this->company->id)
            ->where('year', now()->year)
            ->where('period_type', 'monthly')
            ->where('month', now()->month)
            ->firstOrFail();

        $this->postJson("/api/v1/accounting/fiscal-periods/{$monthly->id}/close")
            ->assertOk();

        $this->assertEquals(0, JournalEntry::where('source_type', JournalEntrySource::CLOSING)->count());
    }

    public function test_closing_annual_period_without_movements_creates_no_entry(): void
    {
        $this->postJson("/api/v1/accounting/fiscal-periods/{$this->annualPeriod()->id}/close")
            ->assertOk();

        $this->assertEquals(0, JournalEntry::where('source_type', JournalEntrySource::CLOSING)->count());
    }
}
