<?php

namespace App\Filament\Resources\KaryawanResource\Pages;

use App\Filament\Resources\KaryawanResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListKaryawans extends ListRecords
{
    protected static string $resource = KaryawanResource::class;

    protected static ?string $title = 'Daftar Karyawan';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Karyawan')
                ->icon('heroicon-o-plus'),
        ];
    }
}

