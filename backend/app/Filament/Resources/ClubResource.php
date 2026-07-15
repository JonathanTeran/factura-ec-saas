<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubResource\Pages;
use App\Models\Arbitros\Club;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClubResource extends Resource
{
    protected static ?string $model = Club::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Clubes';

    protected static ?string $modelLabel = 'Club';

    protected static ?string $pluralModelLabel = 'Clubes';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Club')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Nombre completo oficial: se imprime tal cual en el concepto de la factura'),
                        Forms\Components\TextInput::make('short_name')
                            ->label('Nombre corto')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(100)
                            ->helperText('Se muestra entre paréntesis junto al club. La sincronización la rellena con la provincia si está vacía.'),
                        Forms\Components\Select::make('category')
                            ->label('Categoría')
                            ->options(ChampionshipResource::CATEGORIES),
                        Forms\Components\TextInput::make('external_ref')
                            ->label('Referencia externa'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('short_name')
                    ->label('Nombre corto'),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Ámbito')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->tenant_id ? 'Personal · ' . ($state ?? 'árbitro') : 'Oficial')
                    ->color(fn ($record) => $record->tenant_id ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(ChampionshipResource::CATEGORIES),
                Tables\Filters\TernaryFilter::make('personal')
                    ->label('Ámbito')
                    ->placeholder('Todos')
                    ->trueLabel('Personales (de árbitros)')
                    ->falseLabel('Oficiales')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('tenant_id'),
                        false: fn ($q) => $q->whereNull('tenant_id'),
                        blank: fn ($q) => $q,
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('promote')
                    ->label('Promover a oficial')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->tenant_id !== null)
                    ->requiresConfirmation()
                    ->modalDescription('El club pasará a ser oficial y quedará visible para todos los árbitros.')
                    ->action(function ($record) {
                        $record->update(['tenant_id' => null]);
                        \Filament\Notifications\Notification::make()
                            ->title('Promovido a oficial')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubs::route('/'),
            'create' => Pages\CreateClub::route('/create'),
            'edit' => Pages\EditClub::route('/{record}/edit'),
        ];
    }
}
