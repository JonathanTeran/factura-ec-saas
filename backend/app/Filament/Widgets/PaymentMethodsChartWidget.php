<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Models\Billing\Payment;
use Filament\Widgets\ChartWidget;

class PaymentMethodsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Metodos de Pago (30 dias)';

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $payments = Payment::query()
            ->selectRaw('payment_method, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        if ($payments->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['rgba(156, 163, 175, 0.5)'],
                    ],
                ],
                'labels' => ['Sin pagos recientes'],
            ];
        }

        $palette = [
            'rgba(16, 185, 129, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(20, 184, 166, 0.8)',
        ];

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($payments as $index => $payment) {
            $rawMethod = $payment->getRawOriginal('payment_method');
            $method = $payment->payment_method;

            if (!$method instanceof PaymentMethod) {
                $method = PaymentMethod::tryFrom((string) $rawMethod);
            }

            $labels[] = $method?->label() ?? strtoupper((string) $rawMethod);
            $values[] = (int) $payment->total;
            $colors[] = $palette[$index % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $colors,
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
