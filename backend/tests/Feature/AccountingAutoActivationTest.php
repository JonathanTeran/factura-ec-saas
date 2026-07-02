<?php

namespace Tests\Feature;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FiscalPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class AccountingAutoActivationTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->tenant->update(['has_accounting' => false]);
    }

    public function test_completing_onboarding_activates_basic_accounting(): void
    {
        $this->postJson('/api/v1/onboarding/complete')->assertOk();

        $this->tenant->refresh();
        $this->assertTrue((bool) $this->tenant->has_accounting);

        $setting = AccountingSetting::where('company_id', $this->company->id)->first();
        $this->assertNotNull($setting);
        $this->assertTrue((bool) $setting->auto_journal_entries);

        // Plan de cuentas NIIF PYMES sembrado
        $this->assertGreaterThan(100, Account::where('company_id', $this->company->id)->count());

        // Periodos fiscales del año en curso (12 mensuales + 1 anual)
        $this->assertEquals(
            13,
            FiscalPeriod::where('company_id', $this->company->id)
                ->where('year', now()->year)
                ->count()
        );
    }

    public function test_activation_is_idempotent(): void
    {
        $this->postJson('/api/v1/onboarding/complete')->assertOk();
        $countAfterFirst = Account::where('company_id', $this->company->id)->count();

        $this->postJson('/api/v1/onboarding/complete')->assertOk();

        $this->assertEquals(
            $countAfterFirst,
            Account::where('company_id', $this->company->id)->count()
        );
        $this->assertEquals(1, AccountingSetting::where('company_id', $this->company->id)->count());
    }

    public function test_activation_does_not_override_existing_accounting_setup(): void
    {
        AccountingSetting::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'accounting_standard' => \App\Enums\AccountingStandard::NIIF_FULL,
            'auto_journal_entries' => false,
        ]);

        $this->postJson('/api/v1/onboarding/complete')->assertOk();

        $setting = AccountingSetting::where('company_id', $this->company->id)->first();
        $this->assertEquals(\App\Enums\AccountingStandard::NIIF_FULL, $setting->accounting_standard);
        $this->assertFalse((bool) $setting->auto_journal_entries);
    }
}
