<?php

namespace App\Filament\Resources\FootballMatchResource\Pages;

use App\Filament\Resources\FootballMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFootballMatch extends EditRecord
{
    protected static string $resource = FootballMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
