<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

class EditIzin extends EditRecord
{
    protected static string $resource = IzinResource::class;

    protected static ?string $title = 'Ubah Izin';

    protected static ?string $breadcrumb = 'Ubah';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Izin')
                ->modalDescription('Apakah Anda yakin ingin menghapus data izin ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus'),
            Actions\RestoreAction::make()
                ->label('Pulihkan'),
            Actions\ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->requiresConfirmation()
                ->modalHeading('Hapus Permanen Izin')
                ->modalDescription('Apakah Anda yakin ingin menghapus permanen data izin ini? Tindakan ini tidak dapat dibatalkan.')
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
            ->label('Simpan');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }
}

