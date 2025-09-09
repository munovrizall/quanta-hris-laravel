<?php

namespace App\Filament\Widgets;

use App\Models\Cabang;
use App\Models\Company;
use App\Models\Karyawan;
use App\Models\Perusahaan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
  protected static ?string $pollingInterval = '30s';

  protected function getStats(): array
  {
    return [
      Stat::make('', Karyawan::count())
        ->description('Jumlah Karyawan')
        ->descriptionIcon('heroicon-m-user-group')
        ->color('primary'),

      Stat::make('', Cabang::count())
        ->descriptionIcon('heroicon-m-map-pin')
        ->description('Jumlah Cabang')
        ->color('warning'),

      Stat::make('', Perusahaan::count())
        ->description('Jumlah Perusahaan')
        ->descriptionIcon('heroicon-m-building-office')
        ->color('success'),

    ];
  }
}