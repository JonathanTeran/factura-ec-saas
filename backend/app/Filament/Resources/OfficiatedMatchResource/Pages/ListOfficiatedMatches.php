<?php

namespace App\Filament\Resources\OfficiatedMatchResource\Pages;

use App\Filament\Resources\OfficiatedMatchResource;
use Filament\Resources\Pages\ListRecords;

class ListOfficiatedMatches extends ListRecords
{
    protected static string $resource = OfficiatedMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
