<?php

namespace Tests\Feature\Api;

use App\Enums\PersonalExpenseCategory;
use App\Models\Tenant\PersonalExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class PersonalExpenseBudgetApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function createExpense(array $attrs = []): PersonalExpense
    {
        return PersonalExpense::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'fiscal_year' => 2026,
            'category' => 'food',
            'description' => 'Gasto de prueba',
            'issue_date' => '2026-06-15',
            'amount' => 10,
            ...$attrs,
        ]);
    }

    public function test_get_returns_zero_defaults_for_all_categories(): void
    {
        $response = $this->getJson('/api/v1/personal-expenses-budget?year=2026&month=6');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.month', 6);

        foreach (PersonalExpenseCategory::cases() as $category) {
            $response->assertJsonPath("data.budgets.{$category->value}", 0)
                ->assertJsonPath("data.spent.{$category->value}", 0);
        }
    }

    public function test_put_saves_budgets_and_get_reflects_them(): void
    {
        $this->putJson('/api/v1/personal-expenses-budget', [
            'budgets' => ['housing' => 200, 'food' => 300.5],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.budgets.housing', 200)
            ->assertJsonPath('data.budgets.food', 300.5)
            ->assertJsonPath('data.budgets.health', 0);

        $this->getJson('/api/v1/personal-expenses-budget?year=2026&month=6')
            ->assertOk()
            ->assertJsonPath('data.budgets.housing', 200)
            ->assertJsonPath('data.budgets.food', 300.5)
            ->assertJsonPath('data.budgets.education', 0);
    }

    public function test_put_rejects_invalid_category_and_negative_amount(): void
    {
        $this->putJson('/api/v1/personal-expenses-budget', [
            'budgets' => ['not_a_category' => 100],
        ])->assertStatus(422);

        $this->putJson('/api/v1/personal-expenses-budget', [
            'budgets' => ['housing' => -5],
        ])->assertStatus(422);
    }

    public function test_spent_is_computed_per_category_for_the_month(): void
    {
        $this->createExpense(['category' => 'food', 'issue_date' => '2026-06-05', 'amount' => 25.50]);
        $this->createExpense(['category' => 'food', 'issue_date' => '2026-06-20', 'amount' => 10.25]);
        $this->createExpense(['category' => 'health', 'issue_date' => '2026-06-10', 'amount' => 80]);
        // Other month/year: must be excluded
        $this->createExpense(['category' => 'food', 'issue_date' => '2026-05-31', 'amount' => 999]);
        $this->createExpense(['category' => 'food', 'issue_date' => '2025-06-15', 'amount' => 999, 'fiscal_year' => 2025]);

        $this->getJson('/api/v1/personal-expenses-budget?year=2026&month=6')
            ->assertOk()
            ->assertJsonPath('data.spent.food', 35.75)
            ->assertJsonPath('data.spent.health', 80)
            ->assertJsonPath('data.spent.housing', 0);
    }

    public function test_put_does_not_clobber_other_tenant_settings_keys(): void
    {
        $this->tenant->settings = ['some_other_key' => ['nested' => true], 'flag' => 'yes'];
        $this->tenant->save();

        $this->putJson('/api/v1/personal-expenses-budget', [
            'budgets' => ['tourism' => 50],
        ])->assertOk();

        $this->tenant->refresh();

        $this->assertSame(['nested' => true], $this->tenant->settings['some_other_key']);
        $this->assertSame('yes', $this->tenant->settings['flag']);
        $this->assertEquals(50, $this->tenant->settings['deductible_budget']['tourism']);
    }
}
