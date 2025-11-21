<?php

namespace App\Filament\Resources\CabangResource\Pages;

use App\Filament\Resources\CabangResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListCabangs extends ListRecords
{
    protected static string $resource = CabangResource::class;

    protected static ?string $title = 'Daftar Cabang';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Cabang')
                ->icon('heroicon-o-plus'),
        ];
    }
}

