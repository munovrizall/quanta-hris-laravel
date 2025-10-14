<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Widgets;

use App\Services\LaporanKinerjaService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;

class LateAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Keterlambatan';

    protected static ?string $maxHeight = '320px';

    protected static string $color = 'warning';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $trend = app(LaporanKinerjaService::class)->getMonthlyTrend();

        return [
            'labels' => $trend['labels'],
            'datasets' => [
                [
                    'label' => 'Keterlambatan (%)',
                    'data' => $trend['late_rate'],
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
        ];
    }
}
