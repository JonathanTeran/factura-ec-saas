<?php

namespace App\Filament\Resources\SRICatalogResource\Pages;

use App\Filament\Resources\SRICatalogResource;
use App\Models\SRI\SRICatalog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSRICatalog extends EditRecord
{
    protected static string $resource = SRICatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        SRICatalog::clearCache();
    }
}
