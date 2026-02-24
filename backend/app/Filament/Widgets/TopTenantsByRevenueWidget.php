<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Billing\Payment;
use App\Models\Tenant\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class TopTenantsByRevenueWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Tenants por Ingresos (30 dias)';

    protected static ?int $sort = 12;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        $amountColumn = Payment::reportingAmountColumn();

        return $table
            ->query(
                Tenant::query()
                    ->select([
                        'tenants.id',
                        'tenants.name',
                        DB::raw('COUNT(payments.id) as payments_count'),
                        DB::raw("COALESCE(SUM(payments.{$amountColumn}), 0) as revenue_total"),
                        DB::raw('MAX(payments.paid_at) as last_payment_at'),
                    ])
                    ->leftJoin('payments', function (JoinClause $join) {
                        $join->on('payments.tenant_id', '=', 'tenants.id')
                            ->where('payments.status', '=', PaymentStatus::COMPLETED->value)
                            ->where('payments.paid_at', '>=', now()->subDays(30));
                    })
                    ->whereNull('tenants.deleted_at')
                    ->groupBy('tenants.id', 'tenants.name')
                    ->orderByDesc('revenue_total')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant')
                    ->searchable()
                    ->limit(24),
                Tables\Columns\TextColumn::make('revenue_total')
                    ->label('Ingresos')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('payments_count')
                    ->label('Pagos')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('last_payment_at')
                    ->label('Ultimo pago')
                    ->since()
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->url(fn (Tenant $record) => route('filament.admin.resources.tenants.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin ingresos recientes')
            ->emptyStateDescription('No hay pagos completados en los ultimos 30 dias.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
