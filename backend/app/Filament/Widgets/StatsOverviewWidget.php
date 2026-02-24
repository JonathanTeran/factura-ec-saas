<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\Tenant;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\SRI\ElectronicDocument;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $amountColumn = Payment::reportingAmountColumn();

        // Tenants
        $totalTenants = Tenant::count();
        $newTenantsThisMonth = Tenant::where('created_at', '>=', $currentMonth)->count();
        $newTenantsLastMonth = Tenant::whereBetween('created_at', [$lastMonth, $currentMonth])->count();
        $tenantGrowth = $newTenantsLastMonth > 0
            ? round((($newTenantsThisMonth - $newTenantsLastMonth) / $newTenantsLastMonth) * 100, 1)
            : 100;

        // Revenue
        $revenueThisMonth = Payment::completed()
            ->where('paid_at', '>=', $currentMonth)
            ->sum($amountColumn);
        $revenueLastMonth = Payment::completed()
            ->whereBetween('paid_at', [$lastMonth, $currentMonth])
            ->sum($amountColumn);
        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : 100;

        // Subscriptions
        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)->count();
        $trialingSubscriptions = Subscription::where('status', SubscriptionStatus::TRIALING)->count();

        // Documents
        $documentsThisMonth = ElectronicDocument::withoutGlobalScopes()
            ->where('created_at', '>=', $currentMonth)
            ->count();

        // Pending
        $pendingPayments = Payment::pending()->count();

        return [
            Stat::make('Tenants Activos', number_format($totalTenants))
                ->description($newTenantsThisMonth . ' nuevos este mes')
                ->descriptionIcon($tenantGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($tenantGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getTenantsChartData()),

            Stat::make('Ingresos del Mes', '$' . number_format($revenueThisMonth, 2))
                ->description(($revenueGrowth >= 0 ? '+' : '') . $revenueGrowth . '% vs mes anterior')
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueChartData()),

            Stat::make('Suscripciones Activas', number_format($activeSubscriptions))
                ->description($trialingSubscriptions . ' en período de prueba')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Documentos del Mes', number_format($documentsThisMonth))
                ->description('Emitidos este mes')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Pagos Pendientes', number_format($pendingPayments))
                ->description('Requieren aprobación')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayments > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.payments.index', ['tableFilters[status][value]' => PaymentStatus::PENDING->value])),
        ];
    }

    protected function getTenantsChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Tenant::whereDate('created_at', $date)->count();
        }
        return $data;
    }

    protected function getRevenueChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = (float) Payment::completed()
                ->whereDate('paid_at', $date)
                ->sum(Payment::reportingAmountColumn());
        }
        return $data;
    }
}
