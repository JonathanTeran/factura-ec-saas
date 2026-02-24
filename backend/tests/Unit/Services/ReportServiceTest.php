<?php

namespace Tests\Unit\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\SRI\ElectronicDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Report\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportService();
        $this->tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($user);
    }

    public function test_dashboard_stats_returns_expected_structure(): void
    {
        $stats = $this->service
            ->forTenant($this->tenant)
            ->getDashboardStats();

        $this->assertArrayHasKey('documents', $stats);
        $this->assertArrayHasKey('revenue', $stats);
        $this->assertArrayHasKey('customers', $stats);
        $this->assertArrayHasKey('products', $stats);
        $this->assertArrayHasKey('this_month', $stats['documents']);
        $this->assertArrayHasKey('last_month', $stats['documents']);
    }

    public function test_sales_report_groups_by_day(): void
    {
        $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create authorized invoices
        ElectronicDocument::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'document_type' => DocumentType::FACTURA,
            'status' => DocumentStatus::AUTHORIZED,
            'issue_date' => now(),
            'total' => 100.00,
            'total_tax' => 15.00,
        ]);

        $report = $this->service
            ->forTenant($this->tenant)
            ->getSalesReport(now()->startOfMonth(), now()->endOfMonth(), 'day');

        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('totals', $report);
        $this->assertEquals(3, $report['totals']['count']);
        $this->assertEquals(300.00, $report['totals']['total']);
    }

    public function test_documents_by_status_returns_counts(): void
    {
        $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        ElectronicDocument::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'document_type' => DocumentType::FACTURA,
            'status' => DocumentStatus::AUTHORIZED,
        ]);

        ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'document_type' => DocumentType::FACTURA,
            'status' => DocumentStatus::DRAFT,
        ]);

        $statuses = $this->service
            ->forTenant($this->tenant)
            ->getDocumentsByStatus(now()->startOfMonth(), now()->endOfMonth());

        $this->assertArrayHasKey('authorized', $statuses);
        $this->assertEquals(2, $statuses['authorized']);
    }

    public function test_tax_report_has_correct_subtotals(): void
    {
        $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'document_type' => DocumentType::FACTURA,
            'status' => DocumentStatus::AUTHORIZED,
            'issue_date' => now(),
            'subtotal_0' => 100.00,
            'subtotal_12' => 200.00,
            'subtotal_15' => 300.00,
            'subtotal_5' => 0,
            'subtotal_no_tax' => 0,
            'total_tax' => 69.00,
            'total' => 669.00,
        ]);

        $taxReport = $this->service
            ->forTenant($this->tenant)
            ->getTaxReport(now()->startOfMonth(), now()->endOfMonth());

        $this->assertArrayHasKey('subtotals', $taxReport);
        $this->assertEquals(100.00, $taxReport['subtotals']['0%']);
        $this->assertEquals(200.00, $taxReport['subtotals']['12%']);
        $this->assertEquals(300.00, $taxReport['subtotals']['15%']);
        $this->assertEquals(69.00, $taxReport['total_tax']);
    }

    public function test_period_comparison_calculates_change(): void
    {
        $comparison = $this->service
            ->forTenant($this->tenant)
            ->getPeriodComparison(now()->startOfMonth(), now());

        $this->assertArrayHasKey('current', $comparison);
        $this->assertArrayHasKey('previous', $comparison);
        $this->assertArrayHasKey('changes', $comparison);
    }

    public function test_ats_data_returns_all_sections(): void
    {
        $atsData = $this->service
            ->forTenant($this->tenant)
            ->getATSData(now()->year, now()->month);

        $this->assertArrayHasKey('ventas', $atsData);
        $this->assertArrayHasKey('compras', $atsData);
        $this->assertArrayHasKey('retenciones_emitidas', $atsData);
        $this->assertArrayHasKey('anulados', $atsData);
    }

    public function test_top_customers_ordered_by_amount(): void
    {
        $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        $customer1 = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Top Customer',
        ]);
        $customer2 = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Small Customer',
        ]);

        ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'customer_id' => $customer1->id,
            'document_type' => DocumentType::FACTURA,
            'status' => DocumentStatus::AUTHORIZED,
            'issue_date' => now(),
            'total' => 1000.00,
        ]);

        ElectronicDocument::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'customer_id' => $customer2->id,
            'document_type' => DocumentType::FACTURA,
            'status' => DocumentStatus::AUTHORIZED,
            'issue_date' => now(),
            'total' => 100.00,
        ]);

        $topCustomers = $this->service
            ->forTenant($this->tenant)
            ->getTopCustomers(now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(2, $topCustomers);
        $this->assertEquals($customer1->id, $topCustomers[0]['id']);
    }
}
