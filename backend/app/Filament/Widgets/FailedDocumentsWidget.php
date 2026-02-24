<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentStatus;
use App\Models\SRI\ElectronicDocument;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FailedDocumentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Documentos con Error (7 dias)';

    protected static ?int $sort = 11;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ElectronicDocument::query()
                    ->withoutGlobalScope('tenant')
                    ->with(['tenant:id,name'])
                    ->whereIn('status', [DocumentStatus::FAILED->value, DocumentStatus::REJECTED->value])
                    ->where('created_at', '>=', now()->subDays(7))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hace')
                    ->since(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->limit(20)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state?->shortLabel() ?? '-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->url(fn (ElectronicDocument $record) => route('filament.admin.resources.electronic-documents.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin errores recientes')
            ->emptyStateDescription('No hay documentos rechazados o fallidos en los ultimos 7 dias.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
