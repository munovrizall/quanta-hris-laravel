<?php

namespace App\Filament\Resources\HistoriAbsensiResource\Pages;

use App\Filament\Resources\HistoriAbsensiResource;
use Filament\Resources\Pages\ListRecords;

class ListHistoriAbsensis extends ListRecords
{
    protected static string $resource = HistoriAbsensiResource::class;

    protected static ?string $title = 'Histori Absensi';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
