<?php

namespace App\Filament\Resources\ReferralCommissionResource\Pages;

use App\Filament\Resources\ReferralCommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferralCommission extends ViewRecord
{
    protected static string $resource = ReferralCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
