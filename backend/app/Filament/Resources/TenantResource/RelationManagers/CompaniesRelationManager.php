<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    protected static ?string $title = 'Empresas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ruc')
                    ->label('RUC')
                    ->required()
                    ->maxLength(13)
                    ->minLength(13),
                Forms\Components\TextInput::make('business_name')
                    ->label('Razón Social')
                    ->required()
                    ->maxLength(300),
                Forms\Components\TextInput::make('trade_name')
                    ->label('Nombre Comercial')
                    ->maxLength(300),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Forms\Components\Textarea::make('address')
                    ->label('Dirección')
                    ->required(),
                Forms\Components\Select::make('sri_environment')
                    ->label('Ambiente SRI')
                    ->options([
                        '1' => 'Pruebas',
                        '2' => 'Producción',
                    ])
                    ->default('1')
                    ->required(),
                Forms\Components\Toggle::make('obligated_accounting')
                    ->label('Obligado a llevar contabilidad'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('business_name')
            ->columns([
                Tables\Columns\TextColumn::make('ruc')
                    ->label('RUC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Razón Social')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('sri_environment')
                    ->label('Ambiente')
                    ->formatStateUsing(fn ($state) => $state === '2' ? 'Producción' : 'Pruebas')
                    ->badge()
                    ->color(fn ($state) => $state === '2' ? 'success' : 'warning'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                Tables\Columns\TextColumn::make('signature_expires_at')
                    ->label('Firma expira')
                    ->date('d/m/Y')
                    ->placeholder('Sin firma'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
