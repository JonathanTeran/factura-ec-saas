<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use App\Notifications\PaymentApprovedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $pluralModelLabel = 'Pagos';

    protected static ?int $navigationSort = 25;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', PaymentStatus::PENDING)
            ->whereIn('payment_method', [PaymentMethod::BANK_TRANSFER])
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pago')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID de Transacción')
                            ->disabled(),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('subscription_id')
                            ->label('Suscripción')
                            ->relationship('subscription', 'id')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->disabled(),
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Reembolso')
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(3),

                Forms\Components\Section::make('Método de Pago')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Método')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                            ->disabled(),
                        Forms\Components\TextInput::make('gateway')
                            ->label('Gateway')
                            ->disabled(),
                        Forms\Components\TextInput::make('gateway_transaction_id')
                            ->label('ID Gateway')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Datos de Facturación')
                    ->schema([
                        Forms\Components\TextInput::make('billing_name')
                            ->label('Nombre'),
                        Forms\Components\TextInput::make('billing_email')
                            ->label('Email'),
                        Forms\Components\TextInput::make('billing_identification')
                            ->label('Identificación'),
                        Forms\Components\TextInput::make('billing_phone')
                            ->label('Teléfono'),
                        Forms\Components\Textarea::make('billing_address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                    ])->columns(4),

                Forms\Components\Section::make('Comprobante de Transferencia')
                    ->schema([
                        Forms\Components\FileUpload::make('transfer_receipt_path')
                            ->label('Comprobante')
                            ->image()
                            ->disk('public')
                            ->directory('payment-receipts')
                            ->visibility('public')
                            ->downloadable()
                            ->openable(),
                        Forms\Components\Textarea::make('transfer_reference')
                            ->label('Referencia de transferencia')
                            ->rows(2),
                    ])->columns(2)
                    ->visible(fn ($record) => $record?->payment_method === PaymentMethod::BANK_TRANSFER),

                Forms\Components\Section::make('Notas de Administrador')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notas internas')
                            ->rows(3)
                            ->helperText('Notas visibles solo para administradores'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(10),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PaymentStatus::COMPLETED => 'success',
                        PaymentStatus::PENDING => 'warning',
                        PaymentStatus::PROCESSING => 'info',
                        PaymentStatus::FAILED, PaymentStatus::CANCELED => 'danger',
                        PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PaymentMethod::BANK_TRANSFER => 'warning',
                        PaymentMethod::CREDIT_CARD, PaymentMethod::DEBIT_CARD => 'success',
                        PaymentMethod::STRIPE, PaymentMethod::PAYPHONE, PaymentMethod::KUSHKI => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_approval')
                    ->label('Aprobar')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->getStateUsing(fn ($record) => $record->status === PaymentStatus::PENDING && $record->payment_method === PaymentMethod::BANK_TRANSFER),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de pago')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])),
                Tables\Filters\Filter::make('pending_approval')
                    ->label('Pendientes de aprobación')
                    ->query(fn (Builder $query) => $query
                        ->where('status', PaymentStatus::PENDING)
                        ->where('payment_method', PaymentMethod::BANK_TRANSFER)
                    )
                    ->toggle(),
                Tables\Filters\Filter::make('paid_this_month')
                    ->label('Pagados este mes')
                    ->query(fn ($query) => $query->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Pago por Transferencia')
                    ->modalDescription('¿Confirmas que has verificado el comprobante de transferencia y deseas aprobar este pago? Esto activará la suscripción del cliente.')
                    ->modalSubmitActionLabel('Sí, aprobar pago')
                    ->visible(fn ($record) => $record->status === PaymentStatus::PENDING && $record->payment_method === PaymentMethod::BANK_TRANSFER)
                    ->action(function (Payment $record) {
                        $record->update([
                            'status' => PaymentStatus::COMPLETED,
                            'paid_at' => now(),
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        // Activar suscripción
                        if ($record->subscription) {
                            $endsAt = $record->subscription->billing_cycle === 'yearly'
                                ? now()->addYear()
                                : now()->addMonth();

                            $record->subscription->update([
                                'status' => SubscriptionStatus::ACTIVE,
                                'starts_at' => now(),
                                'ends_at' => $endsAt,
                            ]);

                            // Actualizar tenant
                            $record->tenant->update([
                                'status' => TenantStatus::ACTIVE,
                                'subscription_status' => SubscriptionStatus::ACTIVE,
                            ]);

                            // Sincronizar límites del plan
                            if ($record->subscription->plan) {
                                $record->tenant->syncPlanLimits($record->subscription->plan);
                            }
                        }

                        // Notify customer about approval
                        $owner = $record->tenant?->owner;
                        if ($owner) {
                            $owner->notify(new PaymentApprovedNotification($record));
                        }

                        Notification::make()
                            ->success()
                            ->title('Pago aprobado')
                            ->body('El pago ha sido aprobado y la suscripción ha sido activada.')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar Pago')
                    ->modalDescription('¿Estás seguro de rechazar este pago? Ingresa el motivo del rechazo.')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record->status === PaymentStatus::PENDING && $record->payment_method === PaymentMethod::BANK_TRANSFER)
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'status' => PaymentStatus::FAILED,
                            'failed_at' => now(),
                            'admin_notes' => 'Rechazado: ' . $data['rejection_reason'],
                        ]);

                        // Notify customer about rejection
                        $owner = $record->tenant?->owner;
                        if ($owner) {
                            $owner->notify(new PaymentRejectedNotification($record, $data['rejection_reason']));
                        }

                        Notification::make()
                            ->warning()
                            ->title('Pago rechazado')
                            ->body('El pago ha sido rechazado.')
                            ->send();
                    }),

                Tables\Actions\Action::make('refund')
                    ->label('Reembolsar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Procesar Reembolso')
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Monto a reembolsar')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(fn ($record) => $record->amount),
                        Forms\Components\Textarea::make('refund_reason')
                            ->label('Motivo del reembolso')
                            ->required()
                            ->rows(2),
                    ])
                    ->visible(fn ($record) => $record->status === PaymentStatus::COMPLETED)
                    ->action(function (Payment $record, array $data) {
                        $isFullRefund = $data['refund_amount'] >= $record->amount;

                        // Process refund through PaymentService (handles gateway refund)
                        $paymentService = app(PaymentService::class);
                        $success = $paymentService->processRefund(
                            $record,
                            (float) $data['refund_amount'],
                            $data['refund_reason']
                        );

                        if (!$success) {
                            // Fallback: update record manually if service couldn't handle it
                            $record->update([
                                'status' => $isFullRefund ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED,
                                'refund_amount' => $data['refund_amount'],
                                'refunded_at' => now(),
                                'admin_notes' => ($record->admin_notes ? $record->admin_notes . "\n" : '') . 'Reembolso: ' . $data['refund_reason'],
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Reembolso procesado')
                            ->body('El reembolso ha sido registrado correctamente.')
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_bulk')
                    ->label('Aprobar seleccionados')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        $approved = 0;
                        foreach ($records as $record) {
                            if ($record->status === PaymentStatus::PENDING && $record->payment_method === PaymentMethod::BANK_TRANSFER) {
                                $record->update([
                                    'status' => PaymentStatus::COMPLETED,
                                    'paid_at' => now(),
                                    'approved_by' => auth()->id(),
                                    'approved_at' => now(),
                                ]);

                                if ($record->subscription) {
                                    $endsAt = $record->subscription->billing_cycle === 'yearly'
                                        ? now()->addYear()
                                        : now()->addMonth();

                                    $record->subscription->update([
                                        'status' => SubscriptionStatus::ACTIVE,
                                        'starts_at' => now(),
                                        'ends_at' => $endsAt,
                                    ]);

                                    $record->tenant->update([
                                        'status' => TenantStatus::ACTIVE,
                                        'subscription_status' => SubscriptionStatus::ACTIVE,
                                    ]);

                                    if ($record->subscription->plan) {
                                        $record->tenant->syncPlanLimits($record->subscription->plan);
                                    }
                                }
                                $approved++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title("$approved pagos aprobados")
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Pago')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_id')
                            ->label('ID de Transacción')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('Tenant'),
                        Infolists\Components\TextEntry::make('subscription.plan.name')
                            ->label('Plan'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                PaymentStatus::COMPLETED => 'success',
                                PaymentStatus::PENDING => 'warning',
                                PaymentStatus::FAILED => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(4),

                Infolists\Components\Section::make('Montos')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Monto')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('currency')
                            ->label('Moneda'),
                        Infolists\Components\TextEntry::make('refund_amount')
                            ->label('Reembolsado')
                            ->money('USD'),
                    ])->columns(3),

                Infolists\Components\Section::make('Método de Pago')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Método')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                        Infolists\Components\TextEntry::make('gateway')
                            ->label('Gateway'),
                        Infolists\Components\TextEntry::make('gateway_transaction_id')
                            ->label('ID de Gateway')
                            ->copyable(),
                    ])->columns(3),

                Infolists\Components\Section::make('Comprobante de Transferencia')
                    ->schema([
                        Infolists\Components\ImageEntry::make('transfer_receipt_path')
                            ->label('Comprobante')
                            ->disk('public')
                            ->visibility('public')
                            ->height(300)
                            ->extraImgAttributes(['class' => 'rounded-lg']),
                        Infolists\Components\TextEntry::make('transfer_reference')
                            ->label('Referencia'),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->payment_method === PaymentMethod::BANK_TRANSFER),

                Infolists\Components\Section::make('Fechas')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('Pagado')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('Aprobado')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('refunded_at')
                            ->label('Reembolsado')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(4),

                Infolists\Components\Section::make('Datos de Facturación')
                    ->schema([
                        Infolists\Components\TextEntry::make('billing_name')
                            ->label('Nombre'),
                        Infolists\Components\TextEntry::make('billing_email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('billing_identification')
                            ->label('Identificación'),
                        Infolists\Components\TextEntry::make('billing_phone')
                            ->label('Teléfono'),
                        Infolists\Components\TextEntry::make('billing_address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('Aprobación')
                    ->schema([
                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Aprobado por'),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('Fecha de aprobación')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('Notas de administrador')
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->approved_by !== null),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
