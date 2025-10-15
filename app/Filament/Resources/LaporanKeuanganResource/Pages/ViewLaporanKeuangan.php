<?php

namespace App\Filament\Resources\LaporanKeuanganResource\Pages;

use App\Filament\Resources\LaporanKeuanganResource;
use App\Filament\Resources\LaporanKeuanganResource\Widgets\PayrollCostTrendChart;
use App\Services\LaporanKeuanganService;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Http\RedirectResponse;

class ViewLaporanKeuangan extends Page
{
    protected static string $resource = LaporanKeuanganResource::class;

    protected static string $view = 'filament.resources.laporan-keuangan-resource.pages.view-laporan-keuangan';

    protected static ?string $title = 'Detail Laporan Keuangan';

    protected static ?string $breadcrumb = 'Detail';

    public array $summary = [];

    public array $periodOptions = [];

    public int $selectedYear;

    public int $selectedMonth;

    public ?string $selectedPeriod = null;

    public function mount(int $tahun, int $bulan): void
    {
        $service = app(LaporanKeuanganService::class);

        $this->periodOptions = $service->getAvailablePeriods()->all();

        $selectedPeriod = collect($this->periodOptions)
            ->first(fn(array $period) => $period['tahun'] === (int) $tahun && $period['bulan'] === (int) $bulan);

        if (!$selectedPeriod) {
            Notification::make()
                ->title('Periode tidak ditemukan')
                ->danger()
                ->body('Tidak ada data penggajian untuk periode yang dipilih.')
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

    public function updatedSelectedPeriod(?string $period): ?RedirectResponse
    {
        if (blank($period)) {
            return null;
        }

        [$year, $month] = explode('-', $period);

        return $this->redirect(static::getResource()::getUrl('view', [
            'tahun' => (int) $year,
            'bulan' => (int) $month,
        ]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print_report')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn() => route('laporan-keuangan.cetak', [
                    'tahun' => $this->selectedYear,
                    'bulan' => $this->selectedMonth,
                ]))
                ->openUrlInNewTab()
                ->visible(fn() => !empty($this->summary)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PayrollCostTrendChart::make([
                'highlightPeriod' => $this->selectedPeriod,
            ]),
        ];
    }
}
