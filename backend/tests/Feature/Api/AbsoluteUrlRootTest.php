<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/**
 * Las URLs absolutas de la API (logo de la empresa, avatar…) se arman con
 * APP_URL y no con el Host de la petición. El panel Next.js llama al backend
 * por la red interna de Docker (http://nginx): con el Host de la petición el
 * logo salía como http://nginx/storage/… y el navegador lo mostraba en blanco.
 */
class AbsoluteUrlRootTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    public static function internalRequestRoots(): array
    {
        return [
            'proxy interno por http' => ['http://nginx'],
            'host y esquema distintos a APP_URL' => ['https://nginx'],
        ];
    }

    #[DataProvider('internalRequestRoots')]
    public function test_company_logo_url_uses_the_app_url_not_the_request_host(string $requestRoot): void
    {
        $path = "logos/{$this->tenant->id}/logo.png";
        $this->company->update(['logo_path' => $path]);

        $response = $this->getJson("{$requestRoot}/api/v1/companies/{$this->company->id}");

        $response->assertOk();
        $this->assertSame(
            rtrim(config('app.url'), '/').'/storage/'.$path,
            $response->json('data.company.logo_url')
        );
    }
}
