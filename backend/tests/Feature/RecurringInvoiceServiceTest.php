<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\RecurringInvoice;
use App\Notifications\RecurringInvoiceNotification;
use App\Services\RecurringInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class RecurringInvoiceServiceTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        $this->tenant->update(['has_recurring_invoices' => true]);
        Notification::fake();
        Queue::fake();
    }

    private function recurring(array $attrs = []): RecurringInvoice
    {
        return RecurringInvoice::factory()->monthly()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $this->createCustomer()->id,
            'created_by' => $this->user->id,
            'next_issue_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Plan mensual', 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 15],
            ],
            'auto_send' => false,
        ], $attrs));
    }

    public function test_generates_invoice_through_shared_creator_with_atomic_sequential(): void
    {
        $recurring = $this->recurring(['next_issue_date' => now()->subDays(3)->toDateString()]);

        $results = app(RecurringInvoiceService::class)->processAllDue();

        $this->assertSame(['processed' => 1, 'sent' => 0, 'failed' => 0, 'skipped' => 0], $results);

        $document = ElectronicDocument::where('recurring_invoice_id', $recurring->id)->firstOrFail();
        $this->assertSame('000000001', $document->sequential);
        $this->assertSame(49, strlen((string) $document->access_key));
        $this->assertSame(DocumentStatus::DRAFT, $document->status);
        $this->assertSame(now()->subDays(3)->toDateString(), $document->issue_date->toDateString());
        $this->assertSame(115.0, (float) $document->total);
        $this->assertSame(100.0, (float) $document->subtotal_15);
        $this->assertSame('4', $document->items->first()->tax_percentage_code);
        $this->assertSame(15.0, (float) $document->items->first()->tax_rate);
        $this->assertSame($this->user->id, $document->created_by);

        // El secuencial avanza en la misma tabla que usa el panel.
        $this->assertDatabaseHas('sequential_numbers', [
            'emission_point_id' => $this->emissionPoint->id,
            'document_type' => '01',
            'current_number' => 1,
        ]);
        $this->assertSame(1, $this->tenant->fresh()->documents_this_month);

        $recurring->refresh();
        $this->assertSame(1, $recurring->total_issued);
        $this->assertSame(now()->subDays(3)->addMonthNoOverflow()->toDateString(), $recurring->next_issue_date->toDateString());
        $this->assertNull($recurring->last_error);

        Notification::assertSentTo($this->user, RecurringInvoiceNotification::class, fn ($n) => $n->event === RecurringInvoiceNotification::GENERATED
            && $n->document?->id === $document->id);

        // Una factura creada luego desde el panel toma el siguiente número.
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
        ])->assertCreated()->assertJsonPath('data.document.sequential', '000000002');
    }

    public function test_auto_send_dispatches_sri_processing_when_company_is_ready(): void
    {
        $recurring = $this->recurring(['auto_send' => true]);

        $results = app(RecurringInvoiceService::class)->processAllDue();

        $this->assertSame(1, $results['sent']);
        $document = ElectronicDocument::where('recurring_invoice_id', $recurring->id)->firstOrFail();
        $this->assertSame(DocumentStatus::PROCESSING, $document->status);
        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_auto_send_failure_keeps_document_and_notifies(): void
    {
        // Sin firma: se genera el borrador pero no se envía.
        $this->company->update(['signature_path' => null]);
        $recurring = $this->recurring(['auto_send' => true]);

        $results = app(RecurringInvoiceService::class)->processAllDue();

        $this->assertSame(1, $results['processed']);
        $this->assertSame(0, $results['sent']);
        $this->assertDatabaseHas('electronic_documents', ['recurring_invoice_id' => $recurring->id, 'status' => 'draft']);
        Queue::assertNotPushed(ProcessDocumentJob::class);
        Notification::assertSentTo($this->user, RecurringInvoiceNotification::class, fn ($n) => $n->event === RecurringInvoiceNotification::SEND_FAILED);
    }

    public function test_one_failing_recurring_does_not_break_the_batch(): void
    {
        // Recurrente sobre un establecimiento inactivo: su generación falla y
        // no debe impedir que la siguiente se emita.
        $otherBranch = \App\Models\Tenant\Branch::factory()->create(['tenant_id' => $this->tenant->id, 'company_id' => $this->company->id, 'is_active' => false]);
        $otherPoint = \App\Models\Tenant\EmissionPoint::factory()->create(['tenant_id' => $this->tenant->id, 'branch_id' => $otherBranch->id]);
        $broken = $this->recurring(['branch_id' => $otherBranch->id, 'emission_point_id' => $otherPoint->id]);
        $healthy = $this->recurring();

        $results = app(RecurringInvoiceService::class)->processAllDue();

        $this->assertSame(1, $results['failed']);
        $this->assertSame(1, $results['processed']);
        $this->assertStringContainsString('inactivo', $broken->fresh()->last_error);
        $this->assertNotNull($broken->fresh()->last_error_at);
        $this->assertSame(1, $healthy->fresh()->total_issued);
        // La factura fallida no queda a medias: la transacción se revierte.
        $this->assertDatabaseMissing('electronic_documents', ['recurring_invoice_id' => $broken->id]);
        Notification::assertSentTo($this->user, RecurringInvoiceNotification::class, fn ($n) => $n->event === RecurringInvoiceNotification::FAILED
            && $n->recurring->id === $broken->id);
    }

    public function test_plan_without_feature_records_error_instead_of_generating(): void
    {
        $this->tenant->update(['has_recurring_invoices' => false]);
        $recurring = $this->recurring();

        $results = app(RecurringInvoiceService::class)->processAllDue();

        $this->assertSame(1, $results['failed']);
        $this->assertStringContainsString('plan', $recurring->fresh()->last_error);
        $this->assertDatabaseMissing('electronic_documents', ['recurring_invoice_id' => $recurring->id]);
    }

    public function test_completes_when_max_issues_reached(): void
    {
        $recurring = $this->recurring(['max_issues' => 1]);

        app(RecurringInvoiceService::class)->processAllDue();

        $this->assertSame('completed', $recurring->fresh()->status);
    }

    public function test_reminders_are_sent_once_per_scheduled_date(): void
    {
        $recurring = $this->recurring([
            'next_issue_date' => now()->addDays(2)->toDateString(),
            'notify_before_issue' => true,
            'notify_days_before' => 3,
        ]);
        $this->recurring([
            'next_issue_date' => now()->addDays(10)->toDateString(),
            'notify_days_before' => 3,
        ]);

        $service = app(RecurringInvoiceService::class);

        $this->assertSame(1, $service->sendReminders());
        $this->assertSame(0, $service->sendReminders());
        $this->assertSame(now()->addDays(2)->toDateString(), $recurring->fresh()->reminder_sent_for->toDateString());
        Notification::assertSentTo($this->user, RecurringInvoiceNotification::class, fn ($n) => $n->event === RecurringInvoiceNotification::UPCOMING
            && $n->recurring->id === $recurring->id);
    }
}
