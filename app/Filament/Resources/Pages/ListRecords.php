<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Tables;
use Illuminate\Support\Str;

class ListRecords extends BaseListRecords
{
    protected function getResourceModelLabel(): string
    {
        return Str::lower(static::getResource()::getModelLabel());
    }

    protected function configureDeleteAction(Tables\Actions\DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successNotificationTitle('Berhasil hapus ' . $this->getResourceModelLabel());
    }

    protected function configureDeleteBulkAction(Tables\Actions\DeleteBulkAction $action): void
    {
        parent::configureDeleteBulkAction($action);

        $action->successNotificationTitle('Berhasil hapus ' . $this->getResourceModelLabel());
    }
}


