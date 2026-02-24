<?php

namespace App\Filament\Widgets;

use App\Models\Billing\Payment;
use App\Models\Billing\ReferralCommission;
use App\Models\Billing\Subscription;
use App\Models\SRI\ElectronicDocument;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingActionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Acciones Pendientes';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('status', PaymentStatus::PENDING)
                    ->where('payment_method', PaymentMethod::BANK_TRANSFER)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->limit(20),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->url(fn ($record) => route('filament.admin.resources.payments.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin acciones pendientes')
            ->emptyStateDescription('No hay pagos por transferencia pendientes de aprobación')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function canView(): bool
    {
        return Payment::pending()
            ->where('payment_method', PaymentMethod::BANK_TRANSFER)
            ->exists();
    }
}
