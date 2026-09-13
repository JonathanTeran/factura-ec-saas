<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FefRefereeResource;
use App\Filament\Resources\FefSyncRunResource;
use App\Filament\Resources\FootballMatchResource;
use App\Models\Arbitros\Championship;
use App\Models\Arbitros\Club;
use App\Models\Arbitros\FefReferee;
use App\Models\Arbitros\FefSyncRun;
use App\Models\Arbitros\FootballMatch;
use App\Models\Arbitros\OfficiatedMatch;
use App\Models\Tenant\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Estado de la sincronización FEF en el dashboard del super admin: última
 * corrida y antigüedad, tamaño del catálogo, propuestas pendientes y árbitros.
 * Solo se muestra cuando existen cuentas de árbitro.
 */
class FefSyncStatusWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '60s';

    /** Se renderiza con la página (no lazy): consultas baratas y visible de inmediato. */
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Tenant::where('business_type', Tenant::BUSINESS_TYPE_REFEREE)->exists();
    }

    protected function getStats(): array
    {
        $last = FefSyncRun::latest();
        $lastOk = FefSyncRun::latestOk();
        $stale = FefSyncRun::isStale();

        $syncColor = match (true) {
            $last === null => 'gray',
            $last->status === FefSyncRun::STATUS_FAILED => 'danger',
            $stale => 'warning',
            $last->status === FefSyncRun::STATUS_PARTIAL => 'warning',
            default => 'success',
        };

        $syncDescription = match (true) {
            $last === null => 'Nunca ha corrido',
            $last->status === FefSyncRun::STATUS_RUNNING => 'En curso desde '.$last->started_at->diffForHumans(),
            $stale => 'Sin corrida correcta desde '.($lastOk?->started_at?->diffForHumans() ?? 'nunca'),
            default => $last->statusLabel().' · '.$last->started_at->diffForHumans(),
        };

        $weekAgo = now()->subDays(7);
        $newMatches = FootballMatch::where('published_at', '>=', $weekAgo)->count();
        $pendingProposals = OfficiatedMatch::withoutTenantScope()
            ->where('source', 'scraper')
            ->where('status', OfficiatedMatch::STATUS_PENDING)
            ->count();

        return [
            Stat::make('Sincronización FEF', $last?->started_at?->format('d/m H:i') ?? '—')
                ->description($syncDescription)
                ->descriptionIcon($syncColor === 'success' ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($syncColor)
                ->url(FefSyncRunResource::getUrl()),
            Stat::make('Partidos FEF', number_format(FootballMatch::count()))
                ->description($newMatches.' nuevos en 7 días')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($newMatches > 0 ? 'success' : 'gray')
                ->url(FootballMatchResource::getUrl()),
            Stat::make('Campeonatos y clubes', Championship::where('is_active', true)->count().' / '.Club::whereNull('tenant_id')->count())
                ->description('campeonatos activos / clubes oficiales')
                ->color('info'),
            Stat::make('Propuestas pendientes', number_format($pendingProposals))
                ->description('partidos detectados sin confirmar por el árbitro')
                ->color($pendingProposals > 0 ? 'warning' : 'gray'),
            Stat::make('Árbitros detectados', number_format(FefReferee::count()))
                ->description(FefReferee::withAccount()->count().' con cuenta en Facturón')
                ->color('info')
                ->url(FefRefereeResource::getUrl()),
        ];
    }
}
