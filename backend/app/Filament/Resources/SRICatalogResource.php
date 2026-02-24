<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SRICatalogResource\Pages;
use App\Models\SRI\SRICatalog;
use App\Enums\SRICatalogType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SRICatalogResource extends Resource
{
    protected static ?string $model = SRICatalog::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Catálogos SRI';

    protected static ?string $modelLabel = 'Catálogo SRI';

    protected static ?string $pluralModelLabel = 'Catálogos SRI';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Catálogo')
                    ->schema([
                        Forms\Components\Select::make('catalog_type')
                            ->label('Tipo de Catálogo')
                            ->options(collect(SRICatalogType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('description')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('percentage')
                            ->label('Porcentaje')
                            ->numeric()
                            ->suffix('%')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\TextInput::make('parent_code')
                            ->label('Código Padre')
                            ->maxLength(20)
                            ->nullable()
                            ->helperText('Código del elemento padre para jerarquías'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Datos Adicionales')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('catalog_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => match ($state) {
                        SRICatalogType::TAX_CODE, SRICatalogType::TAX_RATE => 'info',
                        SRICatalogType::IDENTIFICATION_TYPE => 'success',
                        SRICatalogType::PAYMENT_METHOD => 'warning',
                        SRICatalogType::WITHHOLDING_CODE_RENTA, SRICatalogType::WITHHOLDING_CODE_IVA => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('%')
                    ->suffix('%')
                    ->alignCenter()
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('catalog_type')
                    ->label('Tipo')
                    ->options(collect(SRICatalogType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (SRICatalog $record) {
                        $record->update(['is_active' => !$record->is_active]);
                        SRICatalog::clearCache();
                        Notification::make()
                            ->title($record->is_active ? 'Catálogo activado' : 'Catálogo desactivado')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            SRICatalog::clearCache();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            SRICatalog::clearCache();
                        }),
                ]),
            ])
            ->defaultSort('catalog_type')
            ;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSRICatalogs::route('/'),
            'create' => Pages\CreateSRICatalog::route('/create'),
            'edit' => Pages\EditSRICatalog::route('/{record}/edit'),
        ];
    }
}
