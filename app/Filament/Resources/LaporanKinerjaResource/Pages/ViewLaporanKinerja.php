<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Pages;

use App\Filament\Resources\LaporanKinerjaResource;
use App\Filament\Resources\LaporanKinerjaResource\Widgets\PerformanceTrendChart;
use App\Services\LaporanKinerjaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Http\RedirectResponse;

class ViewLaporanKinerja extends Page
{
    protected static string $resource = LaporanKinerjaResource::class;

    protected static string $view = 'filament.resources.laporan-kinerja-resource.pages.view-laporan-kinerja';

    public array $summary = [];

    public array $periodOptions = [];

    public int $selectedYear;

    public int $selectedMonth;

    public ?string $selectedPeriod = null;

    public function mount(int $tahun, int $bulan): void
    {
        $service = app(LaporanKinerjaService::class);

        $this->periodOptions = $service->getAvailablePeriods()->all();

        $selectedPeriod = collect($this->periodOptions)
            ->first(fn (array $period) => $period['tahun'] === (int) $tahun && $period['bulan'] === (int) $bulan);

        if (!$selectedPeriod) {
            Notification::make()
                ->title('Periode tidak ditemukan')
                ->danger()
                ->body('Tidak ada data kinerja untuk periode yang dipilih.')
                ->send();

            $firstPeriod = collect($this->periodOptions)->last();

            if ($firstPeriod) {
                $this->redirect(static::getResource()::getUrl('view', [
                    'tahun' => $firstPeriod['tahun'],
                    'bulan' => $firstPeriod['bulan'],
                ]));
                return;
            }

            $this->redirect(static::getResource()::getUrl('index'));
            return;
        }

        $this->selectedYear = $selectedPeriod['tahun'];
        $this->selectedMonth = $selectedPeriod['bulan'];
        $this->selectedPeriod = sprintf('%04d-%02d', $this->selectedYear, $this->selectedMonth);

        $this->summary = $service->getMonthlySummary($this->selectedYear, $this->selectedMonth);
    }

    public function updatedSelectedPeriod(string $value): RedirectResponse
    {
        $segments = explode('-', $value);

        if (count($segments) !== 2) {
            return redirect()->to(static::getResource()::getUrl('index'));
        }

        [$year, $month] = $segments;

        return redirect()->to(static::getResource()::getUrl('view', [
            'tahun' => (int) $year,
            'bulan' => (int) $month,
        ]));
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PerformanceTrendChart::class,
        ];
    }
}
