<?php

namespace App\Filament\Resources\LaporanKinerjaResource\Pages;

use App\Filament\Resources\LaporanKinerjaResource;
use App\Filament\Resources\LaporanKinerjaResource\Widgets\LateAttendanceChart;
use App\Filament\Resources\LaporanKinerjaResource\Widgets\OnTimeAttendanceChart;
use App\Filament\Resources\Pages\ListRecords;

class ListLaporanKinerjas extends ListRecords
{
    protected static string $resource = LaporanKinerjaResource::class;

    public static function canAccess($record = null): bool
    {
        // konsisten dengan resource
        return LaporanKinerjaResource::canViewAny();
    }

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

