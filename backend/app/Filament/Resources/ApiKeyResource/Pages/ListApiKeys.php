<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiKeys extends ListRecords
{
    protected static string $resource = ApiKeyResource::class;

    protected ?string $subheading = 'Credenciales fec_… con las que plataformas externas (ERP, tiendas en línea, sistemas propios) emiten al SRI a través de /api/v1/ext en nombre de una cuenta.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nueva integración'),
        ];
    }
}
