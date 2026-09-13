<?php

namespace App\Filament\Resources\FootballMatchResource\Pages;

use App\Filament\Resources\FootballMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFootballMatches extends ListRecords
{
    protected static string $resource = FootballMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Sincronizar FEF ahora')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function () {
                    dispatch(new \App\Jobs\Arbitros\SyncFefMatchesJob(\App\Models\Arbitros\FefSyncRun::TRIGGER_MANUAL, auth()->id()));

                    \Filament\Notifications\Notification::make()
                        ->title('Sincronización encolada')
                        ->body('El catálogo FEF y el auto-matching se actualizarán en unos minutos. Sigue el avance en Árbitros → Sincronización FEF.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
