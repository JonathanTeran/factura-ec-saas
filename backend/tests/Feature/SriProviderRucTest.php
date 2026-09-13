<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\SriProviderSettings as SriProviderSettingsPage;
use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Services\Settings\SriProviderSettings;
use App\Services\SRI\DocumentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

/**
 * Resolución NAC-DGERCGC26-00000027: el RUC del proveedor del sistema va en
 * infoAdicional de TODOS los comprobantes y lo administra el super admin.
 */
class SriProviderRucTest extends TestCase
{
    use CreatesTestTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Storage::fake();
        SriProviderSettings::forget();
    }

    protected function tearDown(): void
    {
        SriProviderSettings::forget();
        parent::tearDown();
    }

    private function document(string $factoryState): ElectronicDocument
    {
        $doc = ElectronicDocument::factory()->{$factoryState}()->draft()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'emission_point_id' => $this->emissionPoint->id,
            'customer_id' => $this->createCustomer(['email' => 'cliente@example.com'])->id,
            'created_by' => $this->user->id,
            'additional_info' => $factoryState === 'waybill' ? ['dirPartida' => 'Quito', 'destinatarios' => []] : [],
        ]);

        return $doc->fresh(['items', 'withholdingDetails', 'company', 'customer', 'branch', 'emissionPoint']);
    }

    public function test_provider_ruc_is_added_to_every_document_type_when_configured(): void
    {
        app(SriProviderSettings::class)->save(['enabled' => true, 'provider_ruc' => '1790000000001', 'field_name' => 'RUC Proveedor']);
        $builder = new DocumentBuilder;

        foreach (['invoice', 'liquidacion', 'creditNote', 'debitNote', 'retention', 'waybill'] as $state) {
            $result = $builder->build($this->document($state));

            $this->assertArrayHasKey('infoAdicional', $result, $state);
            $this->assertSame('1790000000001', $result['infoAdicional']['RUC Proveedor'] ?? null, $state);
        }

        // El correo del cliente sigue presente junto al nuevo campo.
        $invoice = $builder->build($this->document('invoice'));
        $this->assertSame('cliente@example.com', $invoice['infoAdicional']['email']);
    }

    public function test_provider_ruc_is_omitted_when_disabled_or_invalid(): void
    {
        $builder = new DocumentBuilder;

        app(SriProviderSettings::class)->save(['enabled' => false, 'provider_ruc' => '1790000000001', 'field_name' => 'RUC Proveedor']);
        $this->assertArrayNotHasKey('RUC Proveedor', $builder->build($this->document('invoice'))['infoAdicional']);

        app(SriProviderSettings::class)->save(['enabled' => true, 'provider_ruc' => '123', 'field_name' => 'RUC Proveedor']);
        $this->assertArrayNotHasKey('RUC Proveedor', $builder->build($this->document('invoice'))['infoAdicional']);
    }

    public function test_env_default_is_used_until_the_admin_saves_a_value(): void
    {
        config(['sri.provider_ruc' => '1790000000001']);
        SriProviderSettings::forget();

        $this->assertSame('1790000000001', app(SriProviderSettings::class)->ruc());
        $this->assertSame('RUC Proveedor', app(SriProviderSettings::class)->fieldName());
    }

    public function test_super_admin_manages_the_provider_ruc_from_the_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'tenant_id' => null, 'is_active' => true]);

        // setUpTenantContext deja Sanctum como guard por defecto: el panel usa 'web'.
        $this->actingAs($admin, 'web')->get('/admin/sri-provider-settings')->assertOk()->assertSee('RUC del proveedor');

        Livewire::actingAs($admin, 'web')
            ->test(SriProviderSettingsPage::class)
            ->fillForm(['enabled' => true, 'provider_ruc' => '1791234567001', 'field_name' => 'RUC Proveedor'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('1791234567001', app(SriProviderSettings::class)->ruc());
        $this->assertDatabaseHas('system_settings', ['key' => 'sri.provider.ruc', 'value' => '1791234567001']);

        // RUC mal formado: error de validación, no se guarda.
        Livewire::actingAs($admin, 'web')
            ->test(SriProviderSettingsPage::class)
            ->fillForm(['enabled' => true, 'provider_ruc' => 'abc', 'field_name' => 'RUC Proveedor'])
            ->call('save')
            ->assertHasFormErrors(['provider_ruc']);
    }
}
