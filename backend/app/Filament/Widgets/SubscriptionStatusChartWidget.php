<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Billing\Subscription;
use Filament\Widgets\ChartWidget;

class SubscriptionStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Estado de Suscripciones';

    protected static ?int $sort = 9;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $labels = [];
        $values = [];
        $colors = [];

        $palette = [
            SubscriptionStatus::ACTIVE->value => 'rgba(16, 185, 129, 0.8)',
            SubscriptionStatus::TRIALING->value => 'rgba(59, 130, 246, 0.8)',
            SubscriptionStatus::PAST_DUE->value => 'rgba(245, 158, 11, 0.8)',
            SubscriptionStatus::INCOMPLETE->value => 'rgba(249, 115, 22, 0.8)',
            SubscriptionStatus::CANCELLED->value => 'rgba(239, 68, 68, 0.8)',
            SubscriptionStatus::EXPIRED->value => 'rgba(107, 114, 128, 0.8)',
        ];

        foreach (SubscriptionStatus::cases() as $status) {
            $count = Subscription::query()
                ->where('status', $status->value)
                ->count();

            if ($count === 0) {
                continue;
            }

            $labels[] = $status->label();
            $values[] = $count;
            $colors[] = $palette[$status->value] ?? 'rgba(156, 163, 175, 0.8)';
        }

        if ($values === []) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['rgba(156, 163, 175, 0.5)'],
                    ],
                ],
                'labels' => ['Sin suscripciones'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Suscripciones',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
