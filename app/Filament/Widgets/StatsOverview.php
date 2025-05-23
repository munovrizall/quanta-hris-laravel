<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
  protected static ?string $pollingInterval = '30s';

  protected function getStats(): array
  {
    return [
      Stat::make('Total Employees', User::count())
        ->description('All registered employees')
        ->descriptionIcon('heroicon-m-user-group')
        ->color('primary'),

      Stat::make('Total Clients', Company::count())
        ->description('All registered companies')
        ->descriptionIcon('heroicon-m-building-office')
        ->color('success'),

      Stat::make('Pending Permissions', \App\Models\Permission::where('approval_status', 'pending')->count())
        ->description('Requests waiting for approval')
        ->descriptionIcon('heroicon-m-clipboard-document-check')
        ->color('warning'),
    ];
  }
}