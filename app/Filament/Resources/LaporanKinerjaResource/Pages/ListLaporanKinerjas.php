<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Pages;

use App\Filament\Resources\LaporanKinerjaResource;
use App\Filament\Resources\LaporanKinerjaResource\Widgets\LateAttendanceChart;
use App\Filament\Resources\LaporanKinerjaResource\Widgets\OnTimeAttendanceChart;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKinerjas extends ListRecords
{
    protected static string $resource = LaporanKinerjaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            OnTimeAttendanceChart::class,
            LateAttendanceChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
