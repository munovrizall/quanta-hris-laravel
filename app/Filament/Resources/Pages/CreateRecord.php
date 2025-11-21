<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;
use Illuminate\Support\Str;

class CreateRecord extends BaseCreateRecord
{
    protected function getResourceModelLabel(): string
    {
        return Str::lower(static::getResource()::getModelLabel());
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Berhasil tambah ' . $this->getResourceModelLabel();
    }
}


