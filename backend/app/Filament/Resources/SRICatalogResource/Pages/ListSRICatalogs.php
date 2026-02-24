<?php

namespace App\Filament\Resources\SRICatalogResource\Pages;

use App\Filament\Resources\SRICatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSRICatalogs extends ListRecords
{
    protected static string $resource = SRICatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
