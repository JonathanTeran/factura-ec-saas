<?php

namespace App\Filament\Resources\CatalogRequestResource\Pages;

use App\Filament\Resources\CatalogRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditCatalogRequest extends EditRecord
{
    protected static string $resource = CatalogRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
