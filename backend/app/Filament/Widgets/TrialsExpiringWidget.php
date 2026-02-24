<?php

namespace App\Filament\Widgets;

use App\Enums\TenantStatus;
use App\Models\Tenant\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TrialsExpiringWidget extends BaseWidget
{
    protected static ?string $heading = 'Trials por Vencer (7 dias)';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->where('status', TenantStatus::TRIAL->value)
                    ->whereNotNull('trial_ends_at')
                    ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                    ->orderBy('trial_ends_at')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant')
                    ->searchable()
                    ->limit(24),
                Tables\Columns\TextColumn::make('owner_email')
                    ->label('Email')
                    ->limit(26)
                    ->tooltip(fn (Tenant $record) => $record->owner_email),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Finaliza')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('days_left')
                    ->label('Dias')
                    ->badge()
                    ->color(fn (int $state) => $state <= 2 ? 'danger' : 'warning')
                    ->getStateUsing(
                        fn (Tenant $record) => max(0, now()->startOfDay()->diffInDays($record->trial_ends_at->startOfDay(), false))
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->url(fn (Tenant $record) => route('filament.admin.resources.tenants.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin trials proximos a vencer')
            ->emptyStateDescription('No hay tenants en periodo de prueba que venzan en los proximos 7 dias.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
