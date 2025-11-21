<?php

namespace App\Filament\Resources\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Illuminate\Support\Str;

class EditRecord extends BaseEditRecord
{
    protected function getResourceModelLabel(): string
    {
        return Str::lower(static::getResource()::getModelLabel());
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Berhasil ubah ' . $this->getResourceModelLabel();
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successNotificationTitle('Berhasil hapus ' . $this->getResourceModelLabel());
    }
}


