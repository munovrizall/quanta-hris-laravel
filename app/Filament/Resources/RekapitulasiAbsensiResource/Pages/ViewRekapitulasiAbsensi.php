<?php

namespace App\Filament\Resources\RekapitulasiAbsensiResource\Pages;

use App\Filament\Resources\RekapitulasiAbsensiResource;
use App\Services\RekapitulasiAbsensiService;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class ViewRekapitulasiAbsensi extends Page
{
    protected static string $resource = RekapitulasiAbsensiResource::class;

    protected static string $view = 'filament.resources.rekapitulasi-absensi-resource.pages.view-rekapitulasi-absensi';

    protected static ?string $title = 'Rekapitulasi Absensi';

    public array $summary = [];

    public array $records = [];

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $selectedPeriod = null;

    public array $periodOptions = [];

    protected RekapitulasiAbsensiService $service;

    public function boot(RekapitulasiAbsensiService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $request = request();

        $default = $this->service->getDefaultPeriod();

        $this->periodOptions = $this->service->getAvailablePeriods()->all();
        $this->selectedPeriod = $request->query('period', null);

        $this->startDate = $request->query('start_date', $default['start']);
        $this->endDate = $request->query('end_date', $default['end']);

        if ($this->selectedPeriod) {
            $this->applySelectedPeriod();
        }

        $this->loadData();
    }

    public function applyFilters(): void
    {
        $this->selectedPeriod = $this->matchSelectedPeriod();
        $this->loadData();
    }

    public function updatedSelectedPeriod(?string $value): void
    {
        if (blank($value)) {
            $this->selectedPeriod = null;
            return;
        }

        $this->applySelectedPeriod();
        $this->loadData();
    }

    public function resetFilters(): void
    {
        $default = $this->service->getDefaultPeriod();

        $this->startDate = $default['start'];
        $this->endDate = $default['end'];
        $this->selectedPeriod = null;

        $this->loadData();
    }

    protected function applySelectedPeriod(): void
    {
        if (!$this->selectedPeriod) {
            return;
        }

        $period = collect($this->periodOptions)
            ->first(fn(array $option) => $option['key'] === $this->selectedPeriod);

        if ($period) {
            $this->startDate = $period['start'];
            $this->endDate = $period['end'];
        }
    }

    protected function matchSelectedPeriod(): ?string
    {
        $period = collect($this->periodOptions)->first(function (array $option) {
            return $option['start'] === $this->startDate && $option['end'] === $this->endDate;
        });

        return $period['key'] ?? null;
    }

    protected function loadData(): void
    {
        [$start, $end] = $this->service->resolvePeriod($this->startDate, $this->endDate);

        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();

        $this->summary = $this->service->getSummary($start, $end);
        $this->records = $this->service->getRekapData($start, $end)->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_pdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn() => route('rekapitulasi-absensi.pdf', [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'download' => 1,
                ]))
                ->openUrlInNewTab()
                ->visible(fn() => !empty($this->records)),
            Actions\Action::make('print_pdf')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->url(fn() => route('rekapitulasi-absensi.pdf', [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ]))
                ->openUrlInNewTab()
                ->visible(fn() => !empty($this->records)),
        ];
    }
}
