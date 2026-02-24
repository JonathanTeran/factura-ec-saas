<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TenantGrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Crecimiento de Tenants';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $data = collect();
        $labels = collect();
        $cumulative = 0;

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels->push($date->translatedFormat('M'));

            $newTenants = Tenant::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $cumulative += $newTenants;
            $data->push($cumulative);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Tenants',
                    'data' => $data->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
