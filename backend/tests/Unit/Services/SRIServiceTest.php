<?php

namespace Tests\Unit\Services;

use App\Enums\DocumentStatus;
use App\Exceptions\SriCommunicationException;
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
    use CreatesTestTenant, RefreshDatabase;

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

        $builder = new DocumentBuilder;
        $result = $builder->build($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));

        $this->assertArrayHasKey('infoTributaria', $result);
        $this->assertArrayHasKey('infoFactura', $result);
        $this->assertArrayHasKey('detalles', $result);
        $this->assertEquals($this->company->ruc, $result['infoTributaria']['ruc']);
        $this->assertEquals($customer->identification, $result['infoFactura']['identificacionComprador']);
        $this->assertCount(1, $result['detalles']);
    }

    public function test_document_builder_creates_liquidacion_array(): void
    {
        // Direcciones sin saltos de línea: el XSD del SRI exige [^\n]*
        // (faker genera direcciones multilínea).
        $this->company->update(['address' => 'Av. República y Eloy Alfaro, Quito']);
        $this->branch->update(['address' => 'Av. Amazonas N34-451, Quito']);

        $supplier = $this->createCustomer([
            'identification_type' => '05',
            'identification' => '1712345678',
            'address' => 'Calle Los Cipreses N65-12, Quito',
        ]);
        $document = ElectronicDocument::factory()->liquidacion()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $supplier->id,
            'created_by' => $this->user->id,
            'subtotal_15' => 100.00,
            'subtotal_12' => 0,
            'subtotal_5' => 0,
            'subtotal_0' => 0,
            'subtotal_no_tax' => 0,
            'total_discount' => 0,
            'total_tax' => 15.00,
            'total_ice' => 0,
            'total' => 115.00,
        ]);

        $document->items()->create([
            'main_code' => 'SERV001',
            'description' => 'Servicio prestado',
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount' => 0,
            'subtotal' => 100.00,
            'tax_code' => '2',
            'tax_percentage_code' => '4',
            'tax_rate' => 15,
            'tax_base' => 100.00,
            'tax_value' => 15.00,
        ]);

        $builder = new DocumentBuilder;
        $result = $builder->build($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));

        $this->assertArrayHasKey('infoTributaria', $result);
        $this->assertArrayHasKey('infoLiquidacionCompra', $result);
        $this->assertArrayHasKey('detalles', $result);
        $this->assertEquals($this->company->ruc, $result['infoTributaria']['ruc']);
        $this->assertEquals('05', $result['infoLiquidacionCompra']['tipoIdentificacionProveedor']);
        $this->assertEquals($supplier->name, $result['infoLiquidacionCompra']['razonSocialProveedor']);
        $this->assertEquals('1712345678', $result['infoLiquidacionCompra']['identificacionProveedor']);
        $this->assertCount(1, $result['detalles']);

        // El array debe producir un XML que valida contra el XSD oficial del
        // paquete (mismo camino que liquidacionCompraFromArray, sin SOAP).
        $result['infoTributaria']['claveAcceso'] = str_repeat('0', 49);
        $result['infoTributaria']['codDoc'] = '03';
        $xml = (new \Teran\Sri\Generators\LiquidacionCompraGenerator)->generate($result);
        $xsd = base_path('vendor/amephia/sri-ec/resources/xsd/liquidacionCompra_v1.1.0.xsd');
        try {
            $this->assertTrue(\Teran\Sri\Schema\XsdValidator::validate($xml, $xsd));
        } catch (\Teran\Sri\Exceptions\ValidationException $e) {
            $this->fail('XSD inválido: ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE) . "\n" . $xml);
        }
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

        $builder = new DocumentBuilder;
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

        $builder = new DocumentBuilder;
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
            ->setConstructorArgs([new DocumentBuilder, $signatureManager, $rideGenerator])
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
            ->setConstructorArgs([new DocumentBuilder, $signatureManager, $rideGenerator])
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

    public function test_sri_service_enters_contingency_on_transient_exception(): void
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
            ->setConstructorArgs([new DocumentBuilder, $signatureManager, $rideGenerator])
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

        $this->expectException(SriCommunicationException::class);

        try {
            $service->process($document->fresh(['items', 'company', 'customer', 'branch', 'emissionPoint']));
        } finally {
            $document->refresh();
            $this->assertEquals(DocumentStatus::PROCESSING, $document->status);
            $this->assertEquals(1, $document->sri_attempts);
            $this->assertTrue((bool) data_get($document->sri_errors, 'contingency_active'));
        }
    }
}
