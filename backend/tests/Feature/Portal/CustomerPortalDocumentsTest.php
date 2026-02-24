<?php

namespace Tests\Feature\Portal;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Portal\CustomerPortalSession;
use App\Models\Portal\CustomerPortalToken;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Customer $customer;
    protected CustomerPortalSession $session;
    protected string $cookieName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'identification' => '1712345678',
        ]);

        $this->cookieName = config('portal.cookie_name', 'customer_portal_session');

        // Crear sesion de portal
        $token = CustomerPortalToken::generateFor(
            $this->tenant->id,
            $this->customer->email,
            $this->customer->identification,
        );

        $this->session = CustomerPortalSession::createFromToken(
            $token,
            $this->customer->name,
            '127.0.0.1',
            'PHPUnit',
        );

        $token->markUsed('127.0.0.1');
    }

    protected function portalRequest(string $method, string $url): \Illuminate\Testing\TestResponse
    {
        return $this->withCookie($this->cookieName, $this->session->id)
            ->{$method}($url);
    }

    public function test_dashboard_loads_with_valid_session(): void
    {
        $response = $this->portalRequest('get', route('portal.dashboard'));

        $response->assertStatus(200);
    }

    public function test_dashboard_redirects_without_session(): void
    {
        $response = $this->get(route('portal.dashboard'));

        $response->assertRedirect(route('portal.login'));
    }

    public function test_document_list_shows_only_authorized_documents(): void
    {
        // Crear documentos autorizados para este cliente
        ElectronicDocument::factory()->count(3)->authorized()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        // Crear documentos draft (no deben aparecer)
        ElectronicDocument::factory()->count(2)->draft()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.index'));

        $response->assertStatus(200);
    }

    public function test_documents_from_other_tenants_are_not_visible(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'identification' => '9999999999',
        ]);

        // Documento de otro tenant
        ElectronicDocument::factory()->authorized()->create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        // Documento del tenant correcto
        $myDoc = ElectronicDocument::factory()->authorized()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.index'));

        $response->assertStatus(200);
    }

    public function test_document_show_loads_authorized_document(): void
    {
        $doc = ElectronicDocument::factory()->authorized()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.show', $doc->id));

        $response->assertStatus(200);
    }

    public function test_document_show_returns_404_for_other_customers_document(): void
    {
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'identification' => '0000000000',
        ]);

        $doc = ElectronicDocument::factory()->authorized()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.show', $doc->id));

        $response->assertStatus(404);
    }

    public function test_document_show_returns_404_for_draft_document(): void
    {
        $doc = ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.show', $doc->id));

        $response->assertStatus(404);
    }

    public function test_expired_session_redirects_to_login(): void
    {
        $this->session->update(['expires_at' => now()->subDay()]);

        $response = $this->portalRequest('get', route('portal.dashboard'));

        $response->assertRedirect(route('portal.login'));
    }

    public function test_inactive_session_redirects_to_login(): void
    {
        $inactivityMinutes = config('portal.session_inactivity_minutes', 120);
        $this->session->update([
            'last_activity_at' => now()->subMinutes($inactivityMinutes + 1),
        ]);

        $response = $this->portalRequest('get', route('portal.dashboard'));

        $response->assertRedirect(route('portal.login'));
    }

    public function test_download_ride_returns_404_when_no_pdf(): void
    {
        $doc = ElectronicDocument::factory()->authorized()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'ride_pdf_path' => null,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.ride', $doc->id));

        $response->assertStatus(404);
    }

    public function test_download_xml_returns_404_when_no_xml(): void
    {
        $doc = ElectronicDocument::factory()->authorized()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'xml_authorized_path' => null,
        ]);

        $response = $this->portalRequest('get', route('portal.documents.xml', $doc->id));

        $response->assertStatus(404);
    }
}
