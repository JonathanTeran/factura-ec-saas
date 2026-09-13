<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant\Tenant;
use App\Services\Arbitros\RefereeModuleActivator;
use App\Services\Cache\TenantCacheService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Al marcar la cuenta como árbitro se activa el vertical (cliente FEF,
     * control de partidos); al volver a negocio solo cambia el tipo y los
     * datos quedan intactos.
     */
    protected function afterSave(): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->getRecord();

        if ($tenant->business_type === Tenant::BUSINESS_TYPE_REFEREE) {
            app(RefereeModuleActivator::class)->activate($tenant);
        }

        TenantCacheService::invalidateTenant($tenant->id);
    }
}
