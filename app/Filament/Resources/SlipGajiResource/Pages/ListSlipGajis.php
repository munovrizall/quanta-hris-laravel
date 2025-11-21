<?php

namespace App\Filament\Resources\SlipGajiResource\Pages;

use App\Filament\Resources\SlipGajiResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListSlipGajis extends ListRecords
{
    protected static string $resource = SlipGajiResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}

