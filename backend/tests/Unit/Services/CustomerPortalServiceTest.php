<?php

namespace Tests\Unit\Services;

use App\Models\Portal\CustomerPortalSession;
use App\Models\Portal\CustomerPortalToken;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use App\Notifications\CustomerPortalMagicLinkNotification;
use App\Services\Portal\CustomerPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerPortalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerPortalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CustomerPortalService::class);
    }

    public function test_find_customer_by_email(): void
    {
        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
        ]);

        $results = $this->service->findCustomerByEmailOrIdentification('test@example.com');

        $this->assertCount(1, $results);
        $this->assertEquals($tenant->id, $results->first()->tenant_id);
    }

    public function test_find_customer_by_identification(): void
    {
        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'identification' => '1712345678',
        ]);

        $results = $this->service->findCustomerByEmailOrIdentification('1712345678');

        $this->assertCount(1, $results);
    }

    public function test_find_customer_in_multiple_tenants(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Customer::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => 'shared@example.com',
            'identification' => '1712345678',
        ]);
        Customer::factory()->create([
            'tenant_id' => $tenant2->id,
            'email' => 'shared@example.com',
            'identification' => '1712345678',
        ]);

        $results = $this->service->findCustomerByEmailOrIdentification('shared@example.com');

        $this->assertCount(2, $results);
    }

    public function test_send_magic_link(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'link@example.com',
            'identification' => '1712345678',
        ]);

        $token = $this->service->sendMagicLink($tenant->id, 'link@example.com', '1712345678');

        $this->assertInstanceOf(CustomerPortalToken::class, $token);
        $this->assertTrue($token->isValid());

        Notification::assertSentOnDemand(CustomerPortalMagicLinkNotification::class);
    }

    public function test_previous_tokens_are_invalidated(): void
    {
        $tenant = Tenant::factory()->create();

        $firstToken = CustomerPortalToken::generateFor($tenant->id, 'test@example.com', '1234567890');
        $secondToken = CustomerPortalToken::generateFor($tenant->id, 'test@example.com', '1234567890');

        $firstToken->refresh();
        $this->assertNotNull($firstToken->used_at);
        $this->assertTrue($secondToken->isValid());
    }

    public function test_authenticate_with_valid_token(): void
    {
        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'auth@example.com',
            'identification' => '1712345678',
            'name' => 'Test Customer',
        ]);

        $token = CustomerPortalToken::generateFor($tenant->id, 'auth@example.com', '1712345678');

        $session = $this->service->authenticateWithToken(
            $token->token,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertInstanceOf(CustomerPortalSession::class, $session);
        $this->assertEquals($tenant->id, $session->tenant_id);
        $this->assertEquals('1712345678', $session->identification);
        $this->assertEquals('Test Customer', $session->customer_name);

        // Token debe estar marcado como usado
        $token->refresh();
        $this->assertNotNull($token->used_at);
    }

    public function test_authenticate_with_expired_token_returns_null(): void
    {
        $tenant = Tenant::factory()->create();

        $token = CustomerPortalToken::generateFor($tenant->id, 'expired@example.com', '1234567890');
        $token->update(['expires_at' => now()->subHour()]);

        $session = $this->service->authenticateWithToken(
            $token->token,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertNull($session);
    }

    public function test_authenticate_with_invalid_token_returns_null(): void
    {
        $session = $this->service->authenticateWithToken(
            'nonexistent-token',
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertNull($session);
    }

    public function test_get_dashboard_stats(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'identification' => '1712345678',
        ]);

        ElectronicDocument::factory()->count(3)->authorized()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'issue_date' => now(),
        ]);

        $token = CustomerPortalToken::generateFor($tenant->id, $customer->email, $customer->identification);
        $session = CustomerPortalSession::createFromToken($token, $customer->name, '127.0.0.1', 'PHPUnit');

        $stats = $this->service->getDashboardStats($session);

        $this->assertEquals(3, $stats['total_documents']);
        $this->assertGreaterThan(0, $stats['total_amount']);
        $this->assertEquals(3, $stats['documents_this_year']);
    }

    public function test_cleanup_expired(): void
    {
        $tenant = Tenant::factory()->create();

        // Crear tokens expirados
        CustomerPortalToken::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'expires_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);

        // Crear sesion expirada
        CustomerPortalSession::create([
            'id' => 'expired-session-123',
            'tenant_id' => $tenant->id,
            'email' => 'test@test.com',
            'identification' => '1234567890',
            'customer_name' => 'Test',
            'last_activity_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);

        $count = $this->service->cleanupExpired();

        $this->assertGreaterThan(0, $count);
    }
}
