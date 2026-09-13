<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\TenantResource\Pages\EditTenant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** El super admin puede marcar una cuenta como árbitro (o volverla a negocio). */
class TenantBusinessTypeAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'tenant_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_admin_marks_tenant_as_referee_and_module_activates(): void
    {
        config(['arbitros.fef.ruc' => '1790000000001', 'arbitros.fef.business_name' => 'FEDERACION ECUATORIANA DE FUTBOL', 'arbitros.fef.email' => 'fef@example.com']);
        $tenant = Tenant::factory()->create(['business_type' => Tenant::BUSINESS_TYPE_GENERIC]);

        Livewire::actingAs($this->superAdmin())
            ->test(EditTenant::class, ['record' => $tenant->getRouteKey()])
            ->fillForm(['business_type' => Tenant::BUSINESS_TYPE_REFEREE])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($tenant->fresh()->isReferee());
        $this->assertTrue(Customer::withoutTenantScope()->where('tenant_id', $tenant->id)->where('identification', '1790000000001')->exists());

        Livewire::actingAs($this->superAdmin())
            ->test(EditTenant::class, ['record' => $tenant->getRouteKey()])
            ->fillForm(['business_type' => Tenant::BUSINESS_TYPE_GENERIC])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($tenant->fresh()->isReferee());
    }

    public function test_tenants_list_shows_account_type(): void
    {
        Tenant::factory()->create(['name' => 'Silbato Pro', 'business_type' => Tenant::BUSINESS_TYPE_REFEREE]);

        $this->actingAs($this->superAdmin())
            ->get('/admin/tenants')
            ->assertOk()
            ->assertSee('Silbato Pro')
            ->assertSee('Árbitro');
    }
}
