<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Filament\Resources\CutiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCuti extends EditRecord
{
    protected static string $resource = CutiResource::class;

    protected static ?string $title = 'Ubah Cuti';

    protected static ?string $breadcrumb = 'Ubah';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Cuti')
                ->modalDescription('Apakah Anda yakin ingin menghapus data cuti ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus'),
            Actions\RestoreAction::make()
                ->label('Pulihkan'),
            Actions\ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->requiresConfirmation()
                ->modalHeading('Hapus Permanen Cuti')
                ->modalDescription('Apakah Anda yakin ingin menghapus permanen data cuti ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus permanen'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }
}