<?php

namespace App\Filament\Resources\ReferralCommissionResource\Pages;

use App\Filament\Resources\ReferralCommissionResource;
use App\Models\Billing\ReferralCommission;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReferralCommissions extends ListRecords
{
    protected static string $resource = ReferralCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(ReferralCommission::count()),
            'pendientes' => Tab::make('Pendientes')
                ->badge(ReferralCommission::pending()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending()),
            'aprobadas' => Tab::make('Aprobadas')
                ->badge(ReferralCommission::approved()->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->approved()),
            'pagadas' => Tab::make('Pagadas')
                ->badge(ReferralCommission::paid()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->paid()),
        ];
    }
}
