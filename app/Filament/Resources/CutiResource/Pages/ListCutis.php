<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Filament\Resources\CutiResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListCutis extends ListRecords
{
    protected static string $resource = CutiResource::class;

    protected static ?string $title = 'Daftar Cuti';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Cuti')
                ->icon('heroicon-o-plus'),
        ];
    }
}

