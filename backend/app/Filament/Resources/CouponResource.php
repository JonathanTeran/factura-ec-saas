<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Billing\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?string $navigationLabel = 'Cupones';

    protected static ?string $modelLabel = 'Cupón';

    protected static ?string $pluralModelLabel = 'Cupones';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Cupón')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->placeholder('Se genera automáticamente si se deja vacío')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->alphaDash(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(500),
                    ])->columns(3),

                Forms\Components\Section::make('Descuento')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('Tipo de descuento')
                            ->options([
                                'percentage' => 'Porcentaje',
                                'fixed' => 'Monto fijo',
                            ])
                            ->required()
                            ->default('percentage')
                            ->live(),
                        Forms\Components\TextInput::make('discount_value')
                            ->label('Valor del descuento')
                            ->numeric()
                            ->required()
                            ->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : 'USD'),
                        Forms\Components\TextInput::make('max_discount_amount')
                            ->label('Descuento máximo')
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Solo para descuentos porcentuales'),
                        Forms\Components\TextInput::make('min_purchase_amount')
                            ->label('Compra mínima')
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(4),

                Forms\Components\Section::make('Restricciones')
                    ->schema([
                        Forms\Components\Select::make('applicable_plans')
                            ->label('Planes aplicables')
                            ->multiple()
                            ->options(\App\Models\Billing\Plan::active()->pluck('name', 'id'))
                            ->helperText('Dejar vacío para aplicar a todos'),
                        Forms\Components\CheckboxList::make('applicable_billing_cycles')
                            ->label('Ciclos de facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                            ]),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Usos máximos totales')
                            ->numeric()
                            ->helperText('Dejar vacío para ilimitado'),
                        Forms\Components\TextInput::make('max_uses_per_tenant')
                            ->label('Usos máximos por tenant')
                            ->numeric()
                            ->default(1),
                    ])->columns(4),

                Forms\Components\Section::make('Duración')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Fecha de inicio'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de expiración')
                            ->after('starts_at'),
                        Forms\Components\Toggle::make('first_payment_only')
                            ->label('Solo primer pago'),
                        Forms\Components\TextInput::make('duration_months')
                            ->label('Duración (meses)')
                            ->numeric()
                            ->helperText('Cuántos meses aplica el descuento'),
                    ])->columns(4),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                        Forms\Components\TextInput::make('current_uses')
                            ->label('Usos actuales')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Descuento')
                    ->formatStateUsing(function ($record) {
                        return $record->getDiscountLabel();
                    }),
                Tables\Columns\TextColumn::make('current_uses')
                    ->label('Usos')
                    ->formatStateUsing(function ($record) {
                        return $record->max_uses
                            ? "{$record->current_uses} / {$record->max_uses}"
                            : $record->current_uses;
                    }),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activos'),
                Tables\Filters\Filter::make('not_expired')
                    ->label('No expirados')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('copy')
                    ->label('Copiar código')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn () => null)
                    ->extraAttributes(fn ($record) => [
                        'x-on:click' => "navigator.clipboard.writeText('{$record->code}')",
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
