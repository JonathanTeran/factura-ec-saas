<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChampionshipResource\Pages;
use App\Models\Arbitros\Championship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChampionshipResource extends Resource
{
    protected static ?string $model = Championship::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Árbitros';

    protected static ?string $navigationLabel = 'Campeonatos';

    protected static ?string $modelLabel = 'Campeonato';

    protected static ?string $pluralModelLabel = 'Campeonatos';

    protected static ?int $navigationSort = 10;

    public const CATEGORIES = [
        'formativa' => 'Formativa',
        'segunda' => 'Segunda',
        'liga_pro' => 'LigaPro',
        'femenino' => 'Femenino',
        'futsal' => 'Futsal',
        'copa' => 'Copa',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campeonato')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('Categoría')
                            ->options(self::CATEGORIES),
                        Forms\Components\TextInput::make('season')
                            ->label('Temporada')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('external_ref')
                            ->label('Referencia externa')
                            ->helperText('ID en la API FEF; lo llena la sincronización'),
                    ])->columns(2),

                Forms\Components\Section::make('Ventana de facturación FEF')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_window_start_day')
                            ->label('Día de inicio')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->helperText('Vacío = usa la ventana global 1–20'),
                        Forms\Components\TextInput::make('invoice_window_end_day')
                            ->label('Día de fin')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->helperText('Vacío = usa la ventana global 1–20'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Si está inactivo, la sincronización lo omite'),
                    ])->columns(3),
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
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('season')
                    ->label('Temporada'),
                Tables\Columns\TextColumn::make('matches_count')
                    ->label('Partidos')
                    ->counts('matches'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
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
                    ->options(self::CATEGORIES),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activos'),
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
                    ->modalDescription('El campeonato pasará a ser oficial y quedará visible para todos los árbitros.')
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
            'index' => Pages\ListChampionships::route('/'),
            'create' => Pages\CreateChampionship::route('/create'),
            'edit' => Pages\EditChampionship::route('/{record}/edit'),
        ];
    }
}
