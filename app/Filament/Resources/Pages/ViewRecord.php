<?php

namespace App\Filament\Resources\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;
use Illuminate\Support\Str;

class ViewRecord extends BaseViewRecord
{
    protected function getResourceModelLabel(): string
    {
        return Str::lower(static::getResource()::getModelLabel());
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successNotificationTitle('Berhasil hapus ' . $this->getResourceModelLabel());
    }
}

