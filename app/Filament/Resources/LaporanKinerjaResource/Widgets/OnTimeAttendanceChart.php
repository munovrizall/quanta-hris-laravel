<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Widgets;

use App\Services\LaporanKinerjaService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;

class OnTimeAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Kehadiran Tepat Waktu';

    protected static ?string $maxHeight = '320px';

    protected static string $color = 'success';

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
                    'label' => 'Kehadiran Tepat Waktu (%)',
                    'data' => $trend['on_time_rate'],
                    'fill' => true, 
                    'tension' => 0.3,
                ],
            ],
        ];
    }
}

