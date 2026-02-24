<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCommissionResource\Pages;
use App\Models\Billing\ReferralCommission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ReferralCommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?string $navigationLabel = 'Comisiones Referidos';

    protected static ?string $modelLabel = 'Comisión';

    protected static ?string $pluralModelLabel = 'Comisiones de Referidos';

    protected static ?int $navigationSort = 18;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Comisión')
                    ->schema([
                        Forms\Components\Select::make('referrer_tenant_id')
                            ->label('Referidor')
                            ->relationship('referrerTenant', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('referred_tenant_id')
                            ->label('Referido')
                            ->relationship('referredTenant', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('payment_id')
                            ->label('Pago Origen')
                            ->relationship('payment', 'invoice_number')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                ReferralCommission::STATUS_PENDING => 'Pendiente',
                                ReferralCommission::STATUS_APPROVED => 'Aprobada',
                                ReferralCommission::STATUS_PAID => 'Pagada',
                                ReferralCommission::STATUS_REJECTED => 'Rechazada',
                            ])
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Tasa (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Monto Comisión')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->default('USD')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Pago de Comisión')
                    ->schema([
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Fecha de Pago'),
                        Forms\Components\TextInput::make('payout_method')
                            ->label('Método de Pago')
                            ->placeholder('Transferencia, PayPal, etc.'),
                        Forms\Components\TextInput::make('payout_reference')
                            ->label('Referencia de Pago')
                            ->placeholder('# de transferencia'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrerTenant.name')
                    ->label('Referidor')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->referrerTenant?->email),
                Tables\Columns\TextColumn::make('referredTenant.name')
                    ->label('Referido')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->referredTenant?->email),
                Tables\Columns\TextColumn::make('payment.invoice_number')
                    ->label('Pago')
                    ->searchable()
                    ->url(fn ($record) => $record->payment_id
                        ? route('filament.admin.resources.payments.view', ['record' => $record->payment_id])
                        : null),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Tasa')
                    ->suffix('%')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Comisión')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        ReferralCommission::STATUS_PENDING => 'warning',
                        ReferralCommission::STATUS_APPROVED => 'info',
                        ReferralCommission::STATUS_PAID => 'success',
                        ReferralCommission::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel()),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        ReferralCommission::STATUS_PENDING => 'Pendiente',
                        ReferralCommission::STATUS_APPROVED => 'Aprobada',
                        ReferralCommission::STATUS_PAID => 'Pagada',
                        ReferralCommission::STATUS_REJECTED => 'Rechazada',
                    ]),
                Tables\Filters\SelectFilter::make('referrer_tenant_id')
                    ->label('Referidor')
                    ->relationship('referrerTenant', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('payable')
                    ->label('Listas para pago')
                    ->query(fn (Builder $query) => $query->where('status', ReferralCommission::STATUS_APPROVED)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (ReferralCommission $record) {
                        $record->approve();
                        Notification::make()
                            ->title('Comisión aprobada')
                            ->body("Comisión de \${$record->commission_amount} lista para pago")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->isPending())
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo de rechazo')
                            ->required(),
                    ])
                    ->action(function (ReferralCommission $record, array $data) {
                        $record->reject($data['reason']);
                        Notification::make()
                            ->title('Comisión rechazada')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Marcar Pagada')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn ($record) => $record->isApproved())
                    ->form([
                        Forms\Components\TextInput::make('method')
                            ->label('Método de pago')
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->required(),
                    ])
                    ->action(function (ReferralCommission $record, array $data) {
                        $record->markAsPaid($data['method'], $data['reference']);
                        Notification::make()
                            ->title('Comisión marcada como pagada')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Aprobar seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->approve();
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("$count comisiones aprobadas")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_pay')
                        ->label('Marcar como pagadas')
                        ->icon('heroicon-o-banknotes')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('method')
                                ->label('Método de pago')
                                ->required(),
                            Forms\Components\TextInput::make('reference')
                                ->label('Referencia')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->isApproved()) {
                                    $record->markAsPaid($data['method'], $data['reference'] . '-' . $record->id);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("$count comisiones marcadas como pagadas")
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListReferralCommissions::route('/'),
            'create' => Pages\CreateReferralCommission::route('/create'),
            'view' => Pages\ViewReferralCommission::route('/{record}'),
            'edit' => Pages\EditReferralCommission::route('/{record}/edit'),
        ];
    }
}
