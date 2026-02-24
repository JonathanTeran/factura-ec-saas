<?php

namespace App\Filament\Widgets;

use App\Models\SRI\ElectronicDocument;
use App\Enums\DocumentStatus;
use Filament\Widgets\ChartWidget;

class DocumentsStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Estado de Documentos (Hoy)';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $today = now()->startOfDay();

        $statuses = [
            DocumentStatus::AUTHORIZED->value => [
                'label' => 'Autorizados',
                'color' => 'rgba(16, 185, 129, 0.8)',
            ],
            DocumentStatus::PROCESSING->value => [
                'label' => 'Procesando',
                'color' => 'rgba(59, 130, 246, 0.8)',
            ],
            DocumentStatus::REJECTED->value => [
                'label' => 'Rechazados',
                'color' => 'rgba(239, 68, 68, 0.8)',
            ],
            DocumentStatus::FAILED->value => [
                'label' => 'Fallidos',
                'color' => 'rgba(245, 158, 11, 0.8)',
            ],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $status => $config) {
            $count = ElectronicDocument::withoutGlobalScopes()
                ->where('status', $status)
                ->where('created_at', '>=', $today)
                ->count();

            if ($count > 0) {
                $data[] = $count;
                $labels[] = $config['label'];
                $colors[] = $config['color'];
            }
        }

        // If no data today, show message
        if (empty($data)) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['rgba(156, 163, 175, 0.5)'],
                    ],
                ],
                'labels' => ['Sin documentos hoy'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
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
