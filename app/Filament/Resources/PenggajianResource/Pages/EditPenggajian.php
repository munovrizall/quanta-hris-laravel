<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Penggajian;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenggajian extends EditRecord
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Ubah Penggajian';

    protected static ?string $breadcrumb = 'Ubah';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Penggajian')
                ->modalDescription('Apakah Anda yakin ingin menghapus seluruh data penggajian untuk periode ini?')
                ->modalSubmitActionLabel('Ya, hapus')
                ->action(function (Penggajian $record) {
                    Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)->delete();
                }),
            Actions\ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->action(function (Penggajian $record) {
                    Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)
                        ->withTrashed()
                        ->forceDelete();
                }),
            Actions\RestoreAction::make()
                ->label('Pulihkan')
                ->action(function (Penggajian $record) {
                    Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)
                        ->withTrashed()
                        ->restore();
                }),
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
