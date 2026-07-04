<?php

namespace Tests\Feature;

use App\Filament\Resources\PlanResource\Pages\EditPlan;
use App\Models\Billing\Plan;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanEditPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_plan_propagates_features_to_its_tenants(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $plan = Plan::factory()->create([
            'has_pos' => false,
            'has_inventory' => false,
            'max_documents_per_month' => 50,
            'price_monthly' => 9.99,
        ]);

        // Dos tenants en ese plan, con las features desactivadas (como el plan).
        $tenants = Tenant::factory()->count(2)->create([
            'current_plan_id' => $plan->id,
            'has_pos' => false,
            'has_inventory' => false,
            'max_documents_per_month' => 50,
        ]);

        $this->actingAs($admin);

        // El admin activa POS e inventario y sube el límite en el plan.
        Livewire::test(EditPlan::class, ['record' => $plan->getRouteKey()])
            ->fillForm([
                'has_pos' => true,
                'has_inventory' => true,
                'max_documents_per_month' => 200,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Los tenants suscritos reciben los cambios.
        foreach ($tenants as $tenant) {
            $fresh = $tenant->fresh();
            $this->assertTrue($fresh->has_pos, 'POS debería propagarse al tenant.');
            $this->assertTrue($fresh->has_inventory, 'Inventario debería propagarse.');
            $this->assertSame(200, $fresh->max_documents_per_month);
        }
    }
}
