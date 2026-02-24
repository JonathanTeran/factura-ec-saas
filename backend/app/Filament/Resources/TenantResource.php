<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\Tenant\Tenant;
use App\Models\Billing\Plan;
use App\Enums\TenantStatus;
use App\Enums\SubscriptionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Tenants';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('owner_email')
                            ->label('Email del propietario')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Estado y Suscripción')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(collect(TenantStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                            ->required(),
                        Forms\Components\Select::make('subscription_status')
                            ->label('Estado Suscripción')
                            ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                            ->required(),
                        Forms\Components\Select::make('current_plan_id')
                            ->label('Plan Actual')
                            ->relationship('currentPlan', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Límites')
                    ->schema([
                        Forms\Components\TextInput::make('max_documents_per_month')
                            ->label('Docs/mes')
                            ->numeric()
                            ->helperText('-1 = ilimitado'),
                        Forms\Components\TextInput::make('max_users')
                            ->label('Usuarios')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_companies')
                            ->label('Empresas/RUCs')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_emission_points')
                            ->label('Puntos emisión')
                            ->numeric(),
                        Forms\Components\TextInput::make('documents_this_month')
                            ->label('Docs este mes')
                            ->numeric()
                            ->disabled(),
                    ])->columns(5),

                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\Toggle::make('has_api_access')
                            ->label('API Access'),
                        Forms\Components\Toggle::make('has_inventory')
                            ->label('Inventario'),
                        Forms\Components\Toggle::make('has_pos')
                            ->label('POS'),
                        Forms\Components\Toggle::make('has_recurring_invoices')
                            ->label('Facturas Recurrentes'),
                        Forms\Components\Toggle::make('has_advanced_reports')
                            ->label('Reportes Avanzados'),
                        Forms\Components\Toggle::make('has_whitelabel_ride')
                            ->label('RIDE Personalizado'),
                        Forms\Components\Toggle::make('has_webhooks')
                            ->label('Webhooks'),
                        Forms\Components\Toggle::make('has_ai_categorization')
                            ->label('IA Categorizacion'),
                        Forms\Components\Toggle::make('has_accounting')
                            ->label('Contabilidad'),
                    ])->columns(4),
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
                Tables\Columns\TextColumn::make('owner_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('currentPlan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (TenantStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Suscripción')
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('documents_this_month')
                    ->label('Docs/Mes')
                    ->alignCenter()
                    ->formatStateUsing(fn ($record) => $record->max_documents_per_month === -1
                        ? $record->documents_this_month
                        : "{$record->documents_this_month}/{$record->max_documents_per_month}"),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('companies_count')
                    ->label('Empresas')
                    ->counts('companies')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(TenantStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('current_plan_id')
                    ->label('Plan')
                    ->relationship('currentPlan', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('login_as')
                    ->label('Impersonar')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => redirect()->route('tenant.impersonate', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\CompaniesRelationManager::class,
            RelationManagers\SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
