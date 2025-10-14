<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Widgets;

use App\Services\LaporanKinerjaService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;

class PerformanceTrendChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?string $maxHeight = '320px';

    protected static string $color = 'info';

    public ?int $year = null;
    public ?int $month = null;

    public function getHeading(): ?string
    {
        $year = $this->year ?? now()->year;
        $month = $this->month ?? now()->month;
        
        $monthName = \App\Utils\MonthHelper::getMonthName($month);
        return "Grafik Kinerja Harian - {$monthName} {$year}";
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Get parameters from widget properties or fallback to current date
        $year = $this->year ?? (int) request()->route('tahun') ?? now()->year;
        $month = $this->month ?? (int) request()->route('bulan') ?? now()->month;

        $performance = app(LaporanKinerjaService::class)->getDailyPerformance($year, $month);

        return [
            'labels' => $performance['labels'],
            'datasets' => [
                [
                    'label' => 'Tepat Waktu (orang)',
                    'data' => $performance['on_time'],
                    'borderColor' => 'rgba(34,197,94,1)', // #22c55e
                    'backgroundColor' => 'rgba(34,197,94,0.15)', // transparan
                    'fill' => true,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Terlambat (orang)',
                    'data' => $performance['late'],
                    'borderColor' => 'rgba(249,115,22,1)', // #f97316
                    'backgroundColor' => 'rgba(254,215,170,0.25)', // transparan
                    'fill' => true,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Lembur (orang)',
                    'data' => $performance['lembur'],
                    'borderColor' => 'rgba(139,69,19,1)', // #8b4513 brown
                    'backgroundColor' => 'rgba(139,69,19,0.15)', // transparan
                    'fill' => true,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Cuti (orang)',
                    'data' => $performance['cuti'],
                    'borderColor' => 'rgba(59,130,246,1)', // #3b82f6 blue
                    'backgroundColor' => 'rgba(59,130,246,0.15)', // transparan
                    'fill' => true,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Izin (orang)',
                    'data' => $performance['izin'],
                    'borderColor' => 'rgba(168,85,247,1)', // #a855f7 purple
                    'backgroundColor' => 'rgba(168,85,247,0.15)', // transparan
                    'fill' => true,
                    'tension' => 0.1,
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
