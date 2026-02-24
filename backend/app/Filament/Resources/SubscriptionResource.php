<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Billing\Subscription;
use App\Enums\SubscriptionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?string $navigationLabel = 'Suscripciones';

    protected static ?string $modelLabel = 'Suscripción';

    protected static ?string $pluralModelLabel = 'Suscripciones';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->required()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->required(),
                        Forms\Components\Select::make('billing_cycle')
                            ->label('Ciclo')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                            ])
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make('Precios')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Descuento')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('final_price')
                            ->label('Precio final')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\Select::make('coupon_id')
                            ->label('Cupón')
                            ->relationship('coupon', 'code')
                            ->searchable(),
                    ])->columns(4),

                Forms\Components\Section::make('Fechas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Inicio'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Fin'),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Fin de prueba'),
                        Forms\Components\DateTimePicker::make('canceled_at')
                            ->label('Cancelado'),
                    ])->columns(4),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Auto-renovar'),
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Método de pago'),
                        Forms\Components\TextInput::make('failed_payments_count')
                            ->label('Pagos fallidos')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Razón cancelación')
                            ->rows(2),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        SubscriptionStatus::ACTIVE => 'success',
                        SubscriptionStatus::TRIALING => 'info',
                        SubscriptionStatus::PAST_DUE => 'warning',
                        SubscriptionStatus::CANCELLED, SubscriptionStatus::EXPIRED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->formatStateUsing(fn ($state) => $state === 'yearly' ? 'Anual' : 'Mensual'),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Vence pronto')
                    ->query(fn ($query) => $query->where('ends_at', '<=', now()->addDays(7))->where('ends_at', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
