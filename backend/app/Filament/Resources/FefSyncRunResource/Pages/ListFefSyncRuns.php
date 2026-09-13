<?php

namespace App\Filament\Resources\FefSyncRunResource\Pages;

use App\Filament\Resources\FefSyncRunResource;
use App\Jobs\Arbitros\SyncFefMatchesJob;
use App\Models\Arbitros\FefSyncRun;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFefSyncRuns extends ListRecords
{
    protected static string $resource = FefSyncRunResource::class;

    protected ?string $subheading = 'Cada hora se descargan campeonatos, clubes y partidos de la API pública de la FEF, se proponen partidos a los árbitros y se actualiza el directorio de árbitros.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Sincronizar ahora')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sincronizar con la FEF')
                ->modalDescription('Se encola una corrida completa (catálogo, propuestas y directorio). Tarda entre 1 y 3 minutos; esta tabla se refresca sola.')
                ->action(function () {
                    dispatch(new SyncFefMatchesJob(FefSyncRun::TRIGGER_MANUAL, auth()->id()));

                    Notification::make()
                        ->title('Sincronización encolada')
                        ->body('Aparecerá aquí en unos segundos como "En curso" y luego con su resultado.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
