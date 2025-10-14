<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Widgets;

use App\Services\LaporanKeuanganService;
use Filament\Widgets\ChartWidget;

class PayrollCostTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Biaya Gaji Bulanan';

    protected static ?string $maxHeight = '320px';

    protected static string $color = 'primary';

    public ?string $highlightPeriod = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $trend = app(LaporanKeuanganService::class)->getMonthlyTrend();

        $highlightKey = $this->highlightPeriod;
        
        return [
            'labels' => $trend['labels'] ?? [],
            'datasets' => [
                [
                    'label' => 'Total Biaya Gaji',
                    'data' => $trend['totals'] ?? [],
                    'borderWidth' => 1,
                    'fill' => true,
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
