<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Billing\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Planes';

    protected static ?string $modelLabel = 'Plan';

    protected static ?string $pluralModelLabel = 'Planes';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Plan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3),
                    ])->columns(3),

                Forms\Components\Section::make('Precios')
                    ->schema([
                        Forms\Components\TextInput::make('price_monthly')
                            ->label('Precio Mensual')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('price_yearly')
                            ->label('Precio Anual')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options(['USD' => 'USD'])
                            ->default('USD'),
                        Forms\Components\TextInput::make('trial_days')
                            ->label('Días de prueba')
                            ->numeric()
                            ->default(14),
                    ])->columns(4),

                Forms\Components\Section::make('Límites')
                    ->schema([
                        Forms\Components\TextInput::make('max_documents_per_month')
                            ->label('Documentos/mes')
                            ->numeric()
                            ->helperText('-1 = ilimitado')
                            ->default(10),
                        Forms\Components\TextInput::make('max_users')
                            ->label('Usuarios')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('max_companies')
                            ->label('Empresas/RUCs')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('max_emission_points')
                            ->label('Puntos emisión')
                            ->numeric()
                            ->default(1),
                    ])->columns(4),

                Forms\Components\Section::make('Características')
                    ->schema([
                        Forms\Components\Toggle::make('has_electronic_signature')
                            ->label('Firma Electrónica'),
                        Forms\Components\Toggle::make('has_api_access')
                            ->label('API Access'),
                        Forms\Components\Toggle::make('has_inventory')
                            ->label('Inventario'),
                        Forms\Components\Toggle::make('has_pos')
                            ->label('POS'),
                        Forms\Components\Toggle::make('has_recurring_invoices')
                            ->label('Facturas Recurrentes'),
                        Forms\Components\Toggle::make('has_proformas')
                            ->label('Proformas'),
                        Forms\Components\Toggle::make('has_ats')
                            ->label('ATS'),
                        Forms\Components\Toggle::make('has_thermal_printer')
                            ->label('Impresora Térmica'),
                        Forms\Components\Toggle::make('has_advanced_reports')
                            ->label('Reportes Avanzados'),
                        Forms\Components\Toggle::make('has_whitelabel_ride')
                            ->label('RIDE Personalizado'),
                        Forms\Components\Toggle::make('has_webhooks')
                            ->label('Webhooks'),
                        Forms\Components\Toggle::make('has_client_portal')
                            ->label('Portal Cliente'),
                        Forms\Components\Toggle::make('has_multi_currency')
                            ->label('Multi-moneda'),
                        Forms\Components\Toggle::make('has_accountant_access')
                            ->label('Acceso Contador'),
                        Forms\Components\Toggle::make('has_ai_categorization')
                            ->label('IA Categorizacion'),
                    ])->columns(4),

                Forms\Components\Section::make('Soporte')
                    ->schema([
                        Forms\Components\Select::make('support_level')
                            ->label('Nivel de Soporte')
                            ->options([
                                'community' => 'Comunidad',
                                'email' => 'Email',
                                'priority' => 'Prioritario',
                                'dedicated' => 'Dedicado',
                            ])
                            ->default('community'),
                        Forms\Components\TextInput::make('support_response_hours')
                            ->label('Tiempo respuesta (hrs)')
                            ->numeric()
                            ->default(72),
                    ])->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Destacado'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Precio/Mes')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_yearly')
                    ->label('Precio/Año')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_documents_per_month')
                    ->label('Docs/Mes')
                    ->formatStateUsing(fn ($state) => $state === -1 ? '∞' : $state),
                Tables\Columns\TextColumn::make('max_users')
                    ->label('Usuarios')
                    ->formatStateUsing(fn ($state) => $state === -1 ? '∞' : $state),
                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Suscriptores')
                    ->counts('tenants'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
