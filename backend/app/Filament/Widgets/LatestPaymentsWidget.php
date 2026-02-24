<?php

namespace App\Filament\Widgets;

use App\Models\Billing\Payment;
use App\Enums\PaymentStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPaymentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Últimos Pagos';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->tenant?->name),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Factura')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PaymentStatus::COMPLETED => 'success',
                        PaymentStatus::PENDING => 'warning',
                        PaymentStatus::FAILED => 'danger',
                        PaymentStatus::REFUNDED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->url(fn ($record) => route('filament.admin.resources.payments.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false);
    }
}
