<?php

namespace Tests\Feature;

use App\Enums\JournalEntrySource;
use App\Models\Accounting\JournalEntry;
use App\Services\Accounting\BasicAccountingActivator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class OpeningBalanceTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        app(BasicAccountingActivator::class)->activate($this->company);
        $this->user->update(['current_company_id' => $this->company->id]);
    }

    private function validPayload(): array
    {
        return [
            'entry_date' => now()->startOfYear()->toDateString(),
            'lines' => [
                ['account_code' => '1.01.01.01', 'debit' => 500.00, 'credit' => 0],
                ['account_code' => '1.01.01.03', 'debit' => 1500.00, 'credit' => 0],
                ['account_code' => '3.01.01', 'debit' => 0, 'credit' => 2000.00],
            ],
        ];
    }

    public function test_creates_posted_opening_entry(): void
    {
        $response = $this->postJson('/api/v1/accounting/opening-balance', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $entry = JournalEntry::where('source_type', JournalEntrySource::OPENING)->first();
        $this->assertNotNull($entry);
        $this->assertEquals('posted', $entry->status->value ?? $entry->status);

        $lines = $entry->lines()->with('account')->get();
        $this->assertEquals(2000.00, (float) $lines->sum('debit'));
        $this->assertEquals(2000.00, (float) $lines->sum('credit'));
        $this->assertEquals(500.00, (float) $lines->firstWhere('account.code', '1.01.01.01')?->debit);
        $this->assertEquals(2000.00, (float) $lines->firstWhere('account.code', '3.01.01')?->credit);
    }

    public function test_rejects_unbalanced_lines(): void
    {
        $payload = $this->validPayload();
        $payload['lines'][2]['credit'] = 1500.00; // descuadre de 500

        $this->postJson('/api/v1/accounting/opening-balance', $payload)
            ->assertStatus(422);

        $this->assertEquals(0, JournalEntry::where('source_type', JournalEntrySource::OPENING)->count());
    }

    public function test_rejects_second_opening_entry_for_same_year(): void
    {
        $this->postJson('/api/v1/accounting/opening-balance', $this->validPayload())
            ->assertCreated();

        $this->postJson('/api/v1/accounting/opening-balance', $this->validPayload())
            ->assertStatus(422);

        $this->assertEquals(1, JournalEntry::where('source_type', JournalEntrySource::OPENING)->count());
    }

    public function test_rejects_unknown_account_code(): void
    {
        $payload = $this->validPayload();
        $payload['lines'][0]['account_code'] = '9.99.99.99';

        $this->postJson('/api/v1/accounting/opening-balance', $payload)
            ->assertStatus(422);
    }
}
