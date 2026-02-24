<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodSettingResource\Pages;
use App\Models\Billing\PaymentMethodSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentMethodSettingResource extends Resource
{
    protected static ?string $model = PaymentMethodSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Métodos de Pago';

    protected static ?string $modelLabel = 'Método de Pago';

    protected static ?string $pluralModelLabel = 'Métodos de Pago';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Método')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(30),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre visible')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción corta')
                            ->rows(2),
                    ])->columns(3),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Habilitado'),
                        Forms\Components\Toggle::make('requires_gateway')
                            ->label('Requiere pasarela de pago'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Instrucciones')
                    ->schema([
                        Forms\Components\Textarea::make('instructions')
                            ->label('Instrucciones para el usuario')
                            ->rows(4)
                            ->placeholder('Ej: Próximamente disponible'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->badge(),
                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Habilitado'),
                Tables\Columns\IconColumn::make('requires_gateway')
                    ->label('Requiere Gateway')
                    ->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethodSettings::route('/'),
            'edit' => Pages\EditPaymentMethodSetting::route('/{record}/edit'),
        ];
    }
}
