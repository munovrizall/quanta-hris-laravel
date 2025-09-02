<?php

namespace App\Filament\Resources\KaryawanResource\Pages;

use App\Filament\Resources\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKaryawan extends EditRecord
{
    protected static string $resource = KaryawanResource::class;

    protected static ?string $title = 'Edit Karyawan';

    protected static ?string $breadcrumb = 'Edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Karyawan')
                ->modalDescription('Apakah Anda yakin ingin menghapus karyawan ini? Data akan diarsipkan dan dapat dipulihkan kembali.')
                ->modalSubmitActionLabel('Ya, hapus'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan karyawan_id tidak berubah saat edit
        $data['karyawan_id'] = $this->record->karyawan_id;
        
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