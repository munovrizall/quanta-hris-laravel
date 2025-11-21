<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected static ?string $title = 'Daftar Absensi';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Absensi')
                ->icon('heroicon-o-plus'),
        ];
    }
}

