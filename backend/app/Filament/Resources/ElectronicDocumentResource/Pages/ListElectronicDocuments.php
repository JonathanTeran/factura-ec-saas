<?php

namespace App\Filament\Resources\ElectronicDocumentResource\Pages;

use App\Filament\Resources\ElectronicDocumentResource;
use App\Models\SRI\ElectronicDocument;
use App\Enums\DocumentStatus;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListElectronicDocuments extends ListRecords
{
    protected static string $resource = ElectronicDocumentResource::class;

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(ElectronicDocument::withoutGlobalScopes()->count()),
            'autorizados' => Tab::make('Autorizados')
                ->badge(ElectronicDocument::withoutGlobalScopes()->authorized()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->authorized()),
            'pendientes' => Tab::make('Pendientes')
                ->badge(ElectronicDocument::withoutGlobalScopes()->pending()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending()),
            'errores' => Tab::make('Con Errores')
                ->badge(ElectronicDocument::withoutGlobalScopes()->failed()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->failed()),
        ];
    }
}
