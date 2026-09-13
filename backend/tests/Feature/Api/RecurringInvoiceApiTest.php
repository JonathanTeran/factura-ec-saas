<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentStatus;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\RecurringInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class RecurringInvoiceApiTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->tenant->update(['has_recurring_invoices' => true]);
        Notification::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $this->createCustomer()->id,
            'name' => 'Hosting mensual',
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 20, 'tax_rate' => 15],
            ],
            'payment_methods' => [['code' => '20', 'term' => 15]],
            'notify_days_before' => 2,
            'auto_send' => false,
        ], $overrides);
    }

    public function test_store_creates_recurring_with_normalized_items(): void
    {
        $this->postJson('/api/v1/recurring-invoices', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.recurring_invoice.status', 'active')
            ->assertJsonPath('data.recurring_invoice.frequency_label', 'Mensual')
            ->assertJsonPath('data.recurring_invoice.next_issue_date', now()->toDateString())
            ->assertJsonPath('data.recurring_invoice.items.0.tax_percentage_code', '4')
            ->assertJsonPath('data.recurring_invoice.estimated_total', 23)
            ->assertJsonPath('data.recurring_invoice.auto_send', false)
            ->assertJsonPath('data.recurring_invoice.customer.name', fn ($v) => is_string($v));
    }

    public function test_store_validates_frequency_and_establishment_coherence(): void
    {
        $this->postJson('/api/v1/recurring-invoices', $this->payload(['frequency' => 'daily']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);

        $otherCompany = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id, 'company_id' => $otherCompany->id]);

        $this->postJson('/api/v1/recurring-invoices', $this->payload(['branch_id' => $otherBranch->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_update_pause_and_resume(): void
    {
        $id = $this->postJson('/api/v1/recurring-invoices', $this->payload())->json('data.recurring_invoice.id');

        $this->putJson("/api/v1/recurring-invoices/{$id}", ['frequency' => 'quarterly', 'max_issues' => 4])
            ->assertOk()
            ->assertJsonPath('data.recurring_invoice.frequency', 'quarterly')
            ->assertJsonPath('data.recurring_invoice.max_issues', 4);

        $this->postJson("/api/v1/recurring-invoices/{$id}/resume")->assertStatus(400);
        $this->postJson("/api/v1/recurring-invoices/{$id}/pause")->assertOk()->assertJsonPath('data.recurring_invoice.status', 'paused');
        $this->postJson("/api/v1/recurring-invoices/{$id}/resume")->assertOk()->assertJsonPath('data.recurring_invoice.status', 'active');
    }

    public function test_generate_now_creates_document_and_advances_schedule(): void
    {
        Queue::fake();
        $id = $this->postJson('/api/v1/recurring-invoices', $this->payload())->json('data.recurring_invoice.id');

        $response = $this->postJson("/api/v1/recurring-invoices/{$id}/generate");

        $response->assertCreated()
            ->assertJsonPath('data.document.status', 'draft')
            ->assertJsonPath('data.document.total', 23)
            ->assertJsonPath('data.recurring_invoice.total_issued', 1)
            ->assertJsonPath('data.recurring_invoice.next_issue_date', now()->addMonthNoOverflow()->toDateString());

        $this->assertDatabaseHas('electronic_documents', [
            'id' => $response->json('data.document.id'),
            'recurring_invoice_id' => $id,
            'sequential' => '000000001',
        ]);
        Queue::assertNotPushed(ProcessDocumentJob::class);

        $this->getJson("/api/v1/recurring-invoices/{$id}/documents")
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/recurring-invoices/{$id}")
            ->assertOk()
            ->assertJsonPath('data.recurring_invoice.generated_documents_count', 1)
            ->assertJsonCount(1, 'data.recurring_invoice.recent_documents');
    }

    public function test_generate_now_sends_to_sri_when_requested(): void
    {
        Queue::fake();
        $id = $this->postJson('/api/v1/recurring-invoices', $this->payload(['auto_send' => true]))->json('data.recurring_invoice.id');

        $this->postJson("/api/v1/recurring-invoices/{$id}/generate")
            ->assertCreated()
            ->assertJsonPath('data.document.status', DocumentStatus::PROCESSING->value);

        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_recurring_scoped_to_tenant(): void
    {
        $id = $this->postJson('/api/v1/recurring-invoices', $this->payload())->json('data.recurring_invoice.id');

        $other = $this->createSecondTenant();
        $other['tenant']->update(['has_recurring_invoices' => true]);
        Sanctum::actingAs($other['user']);

        // El scope global por tenant oculta el registro: no existe para el otro tenant.
        $this->getJson("/api/v1/recurring-invoices/{$id}")->assertStatus(404);
        $this->assertSame(1, RecurringInvoice::withoutTenantScope()->count());
    }
}
