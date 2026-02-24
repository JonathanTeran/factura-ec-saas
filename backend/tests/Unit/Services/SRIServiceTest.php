<?php

namespace Tests\Unit\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\SriException;
use App\Exceptions\SriRejectionException;
use App\Models\SRI\ElectronicDocument;
use App\Services\SRI\DocumentBuilder;
use App\Services\SRI\RIDEGenerator;
use App\Services\SRI\SignatureManager;
use App\Services\SRI\SRIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class SRIServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Storage::fake('s3');
    }

    public function test_document_builder_creates_invoice_array(): void
    {
        $customer = $this->createCustomer();
        $document = ElectronicDocument::factory()->invoice()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
            'subtotal_12' => 100.00,
            'total_tax' => 12.00,
            'total' => 112.00,
        ]);

        $document->items()->create([
            'main_code' => 'PROD001',
            'description' => 'Producto de prueba',
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount' => 0,
            'subtotal' => 100.00,
            'tax_code' => '2',
            'tax_percentage_code' => '2',
            'tax_rate' => 12,
            'tax_base' => 100.00,
            'tax_value' => 12.00,
        ]);

        $builder = new DocumentBuilder();
        $result = $builder->build($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));

        $this->assertArrayHasKey('infoTributaria', $result);
        $this->assertArrayHasKey('infoFactura', $result);
        $this->assertArrayHasKey('detalles', $result);
        $this->assertEquals($this->company->ruc, $result['infoTributaria']['ruc']);
        $this->assertEquals($customer->identification, $result['infoFactura']['identificacionComprador']);
        $this->assertCount(1, $result['detalles']);
    }

    public function test_document_builder_creates_credit_note_array(): void
    {
        $customer = $this->createCustomer();
        $document = ElectronicDocument::factory()->creditNote()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
        ]);

        $document->items()->create([
            'main_code' => 'PROD001',
            'description' => 'Producto devuelto',
            'quantity' => 1,
            'unit_price' => 50.00,
            'discount' => 0,
            'subtotal' => 50.00,
            'tax_code' => '2',
            'tax_percentage_code' => '2',
            'tax_rate' => 12,
            'tax_base' => 50.00,
            'tax_value' => 6.00,
        ]);

        $builder = new DocumentBuilder();
        $result = $builder->build($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));

        $this->assertArrayHasKey('infoTributaria', $result);
        $this->assertArrayHasKey('infoNotaCredito', $result);
        $this->assertArrayHasKey('detalles', $result);
    }

    public function test_document_builder_creates_retention_array(): void
    {
        $customer = $this->createCustomer();
        $document = ElectronicDocument::factory()->retention()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
        ]);

        $builder = new DocumentBuilder();
        $result = $builder->build($document->fresh(['withholdingDetails', 'company', 'customer', 'branch', 'emissionPoint']));

        $this->assertArrayHasKey('infoTributaria', $result);
        $this->assertArrayHasKey('infoCompRetencion', $result);
    }

    public function test_sri_service_handles_authorized_result(): void
    {
        $customer = $this->createCustomer();
        $document = ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
        ]);

        $document->items()->create([
            'main_code' => 'PROD001',
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'subtotal' => 100,
            'tax_code' => '2',
            'tax_percentage_code' => '0',
            'tax_rate' => 0,
            'tax_base' => 100,
            'tax_value' => 0,
        ]);

        // Mock the SRI package
        $mockSri = $this->createMock(\Teran\Sri\SRI::class);
        $mockSri->method('facturaFromArray')->willReturn([
            'claveAcceso' => '1234567890123456789012345678901234567890123456789',
            'xmlFirmado' => '<xml>signed</xml>',
            'xmlAutorizado' => '<xml>authorized</xml>',
            'autorizacion' => (object) [
                'estado' => 'AUTORIZADO',
                'numeroAutorizacion' => '1234567890123456789012345678901234567890123456789',
                'fechaAutorizacion' => now()->format('Y-m-d H:i:s'),
            ],
        ]);

        $signatureManager = $this->createMock(SignatureManager::class);
        $rideGenerator = $this->createMock(RIDEGenerator::class);
        $rideGenerator->method('generate')->willReturn('tenants/1/documents/1/ride.pdf');

        // Use partial mock to prevent forCompany() from creating a real SRI instance
        $service = $this->getMockBuilder(SRIService::class)
            ->setConstructorArgs([new DocumentBuilder(), $signatureManager, $rideGenerator])
            ->onlyMethods(['forCompany'])
            ->getMock();

        $service->expects($this->once())
            ->method('forCompany')
            ->willReturnCallback(function () use ($service, $mockSri) {
                $ref = new \ReflectionProperty(SRIService::class, 'sri');
                $ref->setAccessible(true);
                $ref->setValue($service, $mockSri);
                return $service;
            });

        $result = $service->process($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));

        $document->refresh();
        $this->assertEquals(DocumentStatus::AUTHORIZED, $document->status);
        $this->assertNotNull($document->authorization_number);
    }

    public function test_sri_service_handles_rejected_result(): void
    {
        $customer = $this->createCustomer();
        $document = ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
        ]);

        $document->items()->create([
            'main_code' => 'PROD001',
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'subtotal' => 100,
            'tax_code' => '2',
            'tax_percentage_code' => '0',
            'tax_rate' => 0,
            'tax_base' => 100,
            'tax_value' => 0,
        ]);

        $mockSri = $this->createMock(\Teran\Sri\SRI::class);
        $mockSri->method('facturaFromArray')->willReturn([
            'claveAcceso' => '1234567890123456789012345678901234567890123456789',
            'autorizacion' => (object) [
                'estado' => 'NO AUTORIZADO',
                'mensajes' => ['Error en RUC del emisor'],
            ],
        ]);

        $signatureManager = $this->createMock(SignatureManager::class);
        $rideGenerator = $this->createMock(RIDEGenerator::class);

        // Use partial mock to prevent forCompany() from creating a real SRI instance
        $service = $this->getMockBuilder(SRIService::class)
            ->setConstructorArgs([new DocumentBuilder(), $signatureManager, $rideGenerator])
            ->onlyMethods(['forCompany'])
            ->getMock();

        $service->expects($this->once())
            ->method('forCompany')
            ->willReturnCallback(function () use ($service, $mockSri) {
                $ref = new \ReflectionProperty(SRIService::class, 'sri');
                $ref->setAccessible(true);
                $ref->setValue($service, $mockSri);
                return $service;
            });

        $this->expectException(SriRejectionException::class);

        $service->process($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));
    }

    public function test_sri_service_handles_unexpected_exception(): void
    {
        $customer = $this->createCustomer();
        $document = ElectronicDocument::factory()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $customer->id,
            'created_by' => $this->user->id,
        ]);

        $document->items()->create([
            'main_code' => 'PROD001',
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'subtotal' => 100,
            'tax_code' => '2',
            'tax_percentage_code' => '0',
            'tax_rate' => 0,
            'tax_base' => 100,
            'tax_value' => 0,
        ]);

        $mockSri = $this->createMock(\Teran\Sri\SRI::class);
        $mockSri->method('facturaFromArray')->willThrowException(new \RuntimeException('Connection timeout'));

        $signatureManager = $this->createMock(SignatureManager::class);
        $rideGenerator = $this->createMock(RIDEGenerator::class);

        // Use partial mock to prevent forCompany() from creating a real SRI instance
        $service = $this->getMockBuilder(SRIService::class)
            ->setConstructorArgs([new DocumentBuilder(), $signatureManager, $rideGenerator])
            ->onlyMethods(['forCompany'])
            ->getMock();

        $service->expects($this->once())
            ->method('forCompany')
            ->willReturnCallback(function () use ($service, $mockSri) {
                $ref = new \ReflectionProperty(SRIService::class, 'sri');
                $ref->setAccessible(true);
                $ref->setValue($service, $mockSri);
                return $service;
            });

        $this->expectException(SriException::class);

        $service->process($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));

        $document->refresh();
        $this->assertEquals(DocumentStatus::FAILED, $document->status);
        $this->assertEquals(1, $document->sri_attempts);
    }
}
