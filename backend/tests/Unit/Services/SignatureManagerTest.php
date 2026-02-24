<?php

namespace Tests\Unit\Services;

use App\Exceptions\CertificateNotFoundException;
use App\Models\Tenant\Company;
use App\Services\SRI\SignatureManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class SignatureManagerTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Storage::fake('s3');
    }

    public function test_check_status_returns_missing_when_no_signature(): void
    {
        $company = Company::factory()->create([
            'tenant_id' => $this->tenant->id,
            'signature_path' => null,
        ]);

        $manager = new SignatureManager();
        $result = $manager->checkStatus($company);

        $this->assertEquals('missing', $result['status']);
    }

    public function test_decrypt_throws_exception_when_no_signature(): void
    {
        $company = Company::factory()->create([
            'tenant_id' => $this->tenant->id,
            'signature_path' => null,
        ]);

        $manager = new SignatureManager();

        $this->expectException(CertificateNotFoundException::class);

        $manager->decrypt($company);
    }
}
