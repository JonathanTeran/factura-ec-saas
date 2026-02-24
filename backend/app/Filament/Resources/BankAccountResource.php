<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\Billing\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Cuentas Bancarias';

    protected static ?string $modelLabel = 'Cuenta Bancaria';

    protected static ?string $pluralModelLabel = 'Cuentas Bancarias';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Cuenta')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nombre del Banco')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej: Banco Pichincha'),
                        Forms\Components\Select::make('account_type')
                            ->label('Tipo de Cuenta')
                            ->options([
                                'Ahorros' => 'Ahorros',
                                'Corriente' => 'Corriente',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('account_number')
                            ->label('Número de Cuenta')
                            ->required()
                            ->maxLength(50),
                    ])->columns(3),

                Forms\Components\Section::make('Titular')
                    ->schema([
                        Forms\Components\TextInput::make('holder_name')
                            ->label('Nombre del Titular')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\TextInput::make('holder_identification')
                            ->label('RUC / Cédula del Titular')
                            ->required()
                            ->maxLength(20),
                    ])->columns(2),

                Forms\Components\Section::make('Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('instructions')
                            ->label('Instrucciones adicionales')
                            ->rows(3)
                            ->placeholder('Ej: Incluir número de cédula en el concepto de la transferencia'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Banco')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Número de Cuenta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('holder_name')
                    ->label('Titular')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
