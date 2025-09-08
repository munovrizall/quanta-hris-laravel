<?php

namespace App\Filament\Resources\LemburResource\Pages;

use App\Filament\Resources\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLemburs extends ListRecords
{
    protected static string $resource = LemburResource::class;

    protected static ?string $title = 'Daftar Lembur';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Lembur')
                ->icon('heroicon-o-plus'),
        ];
    }
}