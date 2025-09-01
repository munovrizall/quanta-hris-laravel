<?php

namespace App\Filament\Resources\CabangResource\Pages;

use App\Filament\Resources\CabangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCabang extends EditRecord
{
    protected static string $resource = CabangResource::class;

    protected static ?string $title = 'Ubah Cabang';

    protected static ?string $breadcrumb = 'Ubah';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Cabang')
                ->modalDescription('Apakah Anda yakin ingin menghapus cabang ini? Data akan diarsipkan dan dapat dipulihkan kembali.')
                ->modalSubmitActionLabel('Ya, hapus'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan cabang_id tidak berubah saat edit
        $data['cabang_id'] = $this->record->cabang_id;

        return $data;
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