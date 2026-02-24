<?php

namespace App\Filament\Resources\ElectronicDocumentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Ítems del Documento';

    protected static ?string $modelLabel = 'Ítem';

    protected static ?string $pluralModelLabel = 'Ítems';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('main_code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cant.')
                    ->numeric(2)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('P. Unit.')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Desc.')
                    ->money('USD')
                    ->placeholder('$0.00'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('tax_percentage_code')
                    ->label('IVA %')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '0' => '0%',
                        '2' => '12%',
                        '3', '4' => '15%',
                        '5' => '5%',
                        '6' => 'No obj.',
                        '7' => 'Exento',
                        default => $state,
                    })
                    ->badge()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('tax_value')
                    ->label('Impuesto')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD')),
            ])
            ->paginated(false);
    }
}
