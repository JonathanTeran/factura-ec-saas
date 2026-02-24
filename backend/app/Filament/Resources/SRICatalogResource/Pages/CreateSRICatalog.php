<?php

namespace App\Filament\Resources\SRICatalogResource\Pages;

use App\Filament\Resources\SRICatalogResource;
use App\Models\SRI\SRICatalog;
use Filament\Resources\Pages\CreateRecord;

class CreateSRICatalog extends CreateRecord
{
    protected static string $resource = SRICatalogResource::class;

    protected function afterCreate(): void
    {
        SRICatalog::clearCache();
    }
}
