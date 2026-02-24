<?php

namespace App\Filament\Widgets;

use App\Models\Billing\Subscription;
use App\Models\Billing\Plan;
use App\Enums\SubscriptionStatus;
use Filament\Widgets\ChartWidget;

class SubscriptionsByPlanChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Suscripciones por Plan';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $plans = Plan::where('is_active', true)->get();

        $data = [];
        $labels = [];
        $colors = [
            'rgba(59, 130, 246, 0.8)',   // blue
            'rgba(16, 185, 129, 0.8)',   // green
            'rgba(245, 158, 11, 0.8)',   // amber
            'rgba(239, 68, 68, 0.8)',    // red
            'rgba(139, 92, 246, 0.8)',   // purple
            'rgba(236, 72, 153, 0.8)',   // pink
        ];

        foreach ($plans as $index => $plan) {
            $count = Subscription::where('plan_id', $plan->id)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->count();

            if ($count > 0) {
                $data[] = $count;
                $labels[] = $plan->name;
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
