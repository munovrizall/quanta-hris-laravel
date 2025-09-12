<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenggajians extends ListRecords
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Penggajian';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Penggajian')
                ->icon('heroicon-o-plus'),
        ];
    }
}