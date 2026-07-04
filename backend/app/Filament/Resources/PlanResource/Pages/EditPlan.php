<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\Tenant\Tenant;
use App\Services\Cache\TenantCacheService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Al editar un plan, propaga los nuevos límites y features a todos los
     * tenants suscritos a ese plan (sus columnas están denormalizadas). Sin
     * esto, cambiar un feature en el plan no tendría efecto para los clientes
     * ya suscritos.
     */
    protected function afterSave(): void
    {
        $plan = $this->record;

        $tenants = Tenant::where('current_plan_id', $plan->id)->get();

        foreach ($tenants as $tenant) {
            $tenant->syncPlanLimits($plan);
            TenantCacheService::invalidateTenant($tenant->id);
        }

        if ($tenants->isNotEmpty()) {
            Notification::make()
                ->success()
                ->title('Plan actualizado')
                ->body("Se aplicaron los cambios a {$tenants->count()} tenant(s) suscrito(s) a este plan.")
                ->send();
        }
    }
}
