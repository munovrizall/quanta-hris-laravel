<?php

namespace App\Filament\Widgets;

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
      Stat::make('Total Employees', Karyawan::count())
        ->description('All registered employees')
        ->descriptionIcon('heroicon-m-user-group')
        ->color('primary'),

      Stat::make('Total Clients', Perusahaan::count())
        ->description('All registered companies')
        ->descriptionIcon('heroicon-m-building-office')
        ->color('success'),

      // Stat::make('Pending Permissions', \App\Models\Permission::where('approval_status', 'pending')->count())
      //   ->description('Requests waiting for approval')
      //   ->descriptionIcon('heroicon-m-clipboard-document-check')
      //   ->color('warning'),
    ];
  }
}