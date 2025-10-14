<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Widgets;

use App\Services\LaporanKinerjaService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;

class PerformanceTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Kinerja Bulanan';

    protected static ?string $maxHeight = '320px';

    protected static string $color = 'info';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $year = (int) request()->route('tahun', now()->year);
        $month = (int) request()->route('bulan', now()->month);

        $performance = app(LaporanKinerjaService::class)->getDailyPerformance($year, $month);

        return [
            'labels' => $performance['labels'],
            'datasets' => [
                [
                    'label' => 'Tepat Waktu (orang)',
                    'data' => $performance['on_time'],
                    'borderColor' => Color::Green[500],
                    'backgroundColor' => Color::Green[200],
                    'fill' => false,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Terlambat (orang)',
                    'data' => $performance['late'],
                    'borderColor' => Color::Amber[500],
                    'backgroundColor' => Color::Amber[200],
                    'fill' => false,
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 'full',
        ];
    }
}
