<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentStatus;
use App\Jobs\SRI\ProcessDocumentJob;
use App\Mail\QuoteMail;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmissionPoint;
use App\Models\Tenant\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class QuoteApiTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    private function payload(array $overrides = []): array
    {
        $customer = $overrides['customer'] ?? $this->createCustomer();
        unset($overrides['customer']);

        return array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(15)->toDateString(),
            'payment_terms' => '50% anticipo',
            // Totales falsos: el servidor debe ignorarlos y recalcular.
            'subtotal' => 1,
            'total_tax' => 1,
            'total' => 1,
            'items' => [
                [
                    'description' => 'Consultoría',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount' => 10,
                    'tax_rate' => 15,
                    'subtotal' => 1,
                    'tax_value' => 1,
                    'total' => 1,
                ],
            ],
        ], $overrides);
    }

    public function test_create_quote_computes_totals_server_side(): void
    {
        $response = $this->postJson('/api/v1/quotes', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.quote.quote_number', 'COT-000001')
            ->assertJsonPath('data.quote.status', 'draft')
            ->assertJsonPath('data.quote.subtotal', 190)
            ->assertJsonPath('data.quote.total_discount', 10)
            ->assertJsonPath('data.quote.total_tax', 28.5)
            ->assertJsonPath('data.quote.total', 218.5)
            ->assertJsonPath('data.quote.items.0.subtotal', 190)
            ->assertJsonPath('data.quote.items.0.tax_value', 28.5);
    }

    public function test_quote_numbers_are_sequential_and_unique_per_tenant(): void
    {
        $first = $this->postJson('/api/v1/quotes', $this->payload())->assertCreated();
        $this->postJson('/api/v1/quotes', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.quote.quote_number', 'COT-000002');

        // Borrar la primera no libera su número (antes count()+1 lo repetía).
        $this->deleteJson('/api/v1/quotes/'.$first->json('data.quote.id'))->assertOk();
        $this->postJson('/api/v1/quotes', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.quote.quote_number', 'COT-000003');

        // Otro tenant arranca su propia serie.
        $other = $this->createSecondTenant();
        $company2 = Company::factory()->create(['tenant_id' => $other['tenant']->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $other['tenant']->id]);
        Sanctum::actingAs($other['user']);
        config(['app.tenant_id' => $other['tenant']->id]);

        $this->postJson('/api/v1/quotes', $this->payload([
            'company_id' => $company2->id,
            'customer' => $customer2,
        ]))->assertCreated()->assertJsonPath('data.quote.quote_number', 'COT-000001');
    }

    public function test_update_recalculates_totals_and_rejects_foreign_products(): void
    {
        $quote = $this->postJson('/api/v1/quotes', $this->payload())->json('data.quote');
        $other = $this->createSecondTenant();
        $foreignProduct = \App\Models\Tenant\Product::factory()->create(['tenant_id' => $other['tenant']->id]);

        $this->putJson("/api/v1/quotes/{$quote['id']}", [
            'items' => [['product_id' => $foreignProduct->id, 'description' => 'x', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.product_id']);

        $this->putJson("/api/v1/quotes/{$quote['id']}", [
            'notes' => 'Entrega en 5 días',
            'items' => [['description' => 'Servicio', 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0]],
        ])->assertOk()
            ->assertJsonPath('data.quote.notes', 'Entrega en 5 días')
            ->assertJsonPath('data.quote.total', 100)
            ->assertJsonPath('data.quote.total_tax', 0);
    }

    public function test_send_queues_email_with_pdf_and_marks_sent(): void
    {
        Mail::fake();
        $customer = $this->createCustomer(['email' => 'cliente@example.com']);
        $quote = $this->postJson('/api/v1/quotes', $this->payload(['customer' => $customer]))->json('data.quote');

        $this->postJson("/api/v1/quotes/{$quote['id']}/send", ['message' => 'Adjunto propuesta'])
            ->assertOk()
            ->assertJsonPath('data.quote.status', 'sent')
            ->assertJsonPath('data.quote.sent_to', 'cliente@example.com');

        Mail::assertQueued(QuoteMail::class, fn (QuoteMail $mail) => $mail->quote->id === $quote['id']
            && $mail->customMessage === 'Adjunto propuesta'
            && $mail->hasTo('cliente@example.com'));
    }

    public function test_send_requires_a_recipient(): void
    {
        Mail::fake();
        $customer = $this->createCustomer(['email' => null]);
        $quote = $this->postJson('/api/v1/quotes', $this->payload(['customer' => $customer]))->json('data.quote');

        $this->postJson("/api/v1/quotes/{$quote['id']}/send")->assertStatus(422);

        $this->postJson("/api/v1/quotes/{$quote['id']}/send", ['email' => 'otro@example.com'])
            ->assertOk()
            ->assertJsonPath('data.quote.sent_to', 'otro@example.com');
        Mail::assertQueued(QuoteMail::class);
    }

    public function test_accept_reject_transitions(): void
    {
        $quote = $this->postJson('/api/v1/quotes', $this->payload())->json('data.quote');

        $this->postJson("/api/v1/quotes/{$quote['id']}/accept")->assertOk()->assertJsonPath('data.quote.status', 'accepted');
        // Aceptada: ya no se edita.
        $this->putJson("/api/v1/quotes/{$quote['id']}", ['notes' => 'x'])->assertStatus(400);
        $this->postJson("/api/v1/quotes/{$quote['id']}/reject")->assertOk()->assertJsonPath('data.quote.status', 'rejected');
        $this->postJson("/api/v1/quotes/{$quote['id']}/accept")->assertStatus(400);
    }

    public function test_convert_creates_invoice_draft_and_marks_quote_invoiced(): void
    {
        Queue::fake();
        $product = $this->createProduct(['main_code' => 'SERV-01', 'tax_rate' => 15, 'tax_percentage_code' => '4']);
        $quote = $this->postJson('/api/v1/quotes', $this->payload([
            'items' => [
                ['product_id' => $product->id, 'description' => 'Servicio', 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 15],
                ['description' => 'Extra', 'quantity' => 2, 'unit_price' => 5, 'tax_rate' => 0],
            ],
        ]))->json('data.quote');

        $response = $this->postJson("/api/v1/quotes/{$quote['id']}/convert", [
            'emission_point_id' => $this->emissionPoint->id,
            'send' => false,
            'payment_method' => '01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sent', false)
            ->assertJsonPath('data.quote.status', 'invoiced')
            ->assertJsonPath('data.document.status', 'draft')
            ->assertJsonPath('data.document.sequential', '000000001')
            ->assertJsonPath('data.document.total', 125)
            ->assertJsonPath('data.document.subtotal_15', 100)
            ->assertJsonPath('data.document.subtotal_0', 10)
            ->assertJsonPath('data.document.additional_info.Cotización', 'COT-000001');

        $documentId = $response->json('data.document.id');
        $this->assertSame($documentId, $response->json('data.quote.converted_to_document_id'));
        $this->assertDatabaseHas('document_items', ['electronic_document_id' => $documentId, 'main_code' => 'SERV-01']);
        $this->assertDatabaseHas('document_items', ['electronic_document_id' => $documentId, 'main_code' => 'ITEM-2', 'tax_percentage_code' => '0']);
        $this->assertNotNull(\App\Models\SRI\ElectronicDocument::find($documentId)->access_key);
        $this->assertSame(1, $this->tenant->fresh()->documents_this_month);
        Queue::assertNotPushed(ProcessDocumentJob::class);

        // No se convierte dos veces ni se borra ya facturada.
        $this->postJson("/api/v1/quotes/{$quote['id']}/convert", ['emission_point_id' => $this->emissionPoint->id])->assertStatus(400);
        $this->deleteJson("/api/v1/quotes/{$quote['id']}")->assertStatus(400);
    }

    public function test_convert_and_send_dispatches_sri_processing(): void
    {
        Queue::fake();
        $quote = $this->postJson('/api/v1/quotes', $this->payload())->json('data.quote');

        $this->postJson("/api/v1/quotes/{$quote['id']}/convert", [
            'emission_point_id' => $this->emissionPoint->id,
            'send' => true,
        ])->assertCreated()
            ->assertJsonPath('data.sent', true)
            ->assertJsonPath('data.document.status', DocumentStatus::PROCESSING->value);

        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_convert_requires_emission_point_of_the_quote_company(): void
    {
        $otherCompany = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id, 'company_id' => $otherCompany->id]);
        $otherPoint = EmissionPoint::factory()->create(['tenant_id' => $this->tenant->id, 'branch_id' => $otherBranch->id]);
        $quote = $this->postJson('/api/v1/quotes', $this->payload())->json('data.quote');

        $this->postJson("/api/v1/quotes/{$quote['id']}/convert", ['emission_point_id' => $otherPoint->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['emission_point_id']);
    }

    public function test_pdf_url_streams_a_pdf_without_session(): void
    {
        $quote = $this->postJson('/api/v1/quotes', $this->payload())->json('data.quote');

        $meta = $this->getJson("/api/v1/quotes/{$quote['id']}/pdf")->assertOk()->json('data');
        $this->assertSame('cotizacion-COT-000001.pdf', $meta['filename']);

        $parts = parse_url($meta['url']);
        $response = $this->get($parts['path'].'?'.$parts['query']);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Enlace manipulado: rechazado.
        $this->get($parts['path'].'?e='.now()->addHour()->timestamp.'&t=malo')->assertStatus(403);
    }

    public function test_expire_command_marks_open_quotes_past_expiry(): void
    {
        $expiredSent = Quote::factory()->sent()->create([
            'tenant_id' => $this->tenant->id, 'company_id' => $this->company->id,
            'customer_id' => $this->createCustomer()->id, 'created_by' => $this->user->id,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
        $acceptedOld = Quote::factory()->accepted()->create([
            'tenant_id' => $this->tenant->id, 'company_id' => $this->company->id,
            'customer_id' => $this->createCustomer()->id, 'created_by' => $this->user->id,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
        $stillValid = Quote::factory()->create([
            'tenant_id' => $this->tenant->id, 'company_id' => $this->company->id,
            'customer_id' => $this->createCustomer()->id, 'created_by' => $this->user->id,
            'expiry_date' => now()->toDateString(),
        ]);

        $this->artisan('quotes:expire')->assertExitCode(0);

        $this->assertSame('expired', $expiredSent->fresh()->status->value);
        $this->assertSame('accepted', $acceptedOld->fresh()->status->value);
        $this->assertSame('draft', $stillValid->fresh()->status->value);
    }

    public function test_quotes_blocked_when_plan_lacks_proformas(): void
    {
        $this->tenant->update(['has_proformas' => false]);

        $this->getJson('/api/v1/quotes')
            ->assertStatus(403)
            ->assertJson(['feature' => 'proformas']);
    }

    public function test_quote_scoped_to_tenant(): void
    {
        $quote = $this->postJson('/api/v1/quotes', $this->payload())->json('data.quote');

        $other = $this->createSecondTenant();
        Sanctum::actingAs($other['user']);

        // El scope global por tenant oculta el registro: no existe para el otro tenant.
        $this->getJson("/api/v1/quotes/{$quote['id']}")->assertStatus(404);
        $this->postJson("/api/v1/quotes/{$quote['id']}/accept")->assertStatus(404);
    }
}
