<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Filament\Resources\CutiResource;
use App\Models\Cuti;
use App\Models\Karyawan;
use Carbon\Carbon;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

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

    protected function afterSave(): void
    {
        $record = $this->record; // Cuti
        if (!$record instanceof Cuti) {
            return;
        }

        // Jika status berubah menjadi Disetujui dari bukan Disetujui, kurangi kuota cuti tahunan
        if ($record->wasChanged('status_cuti') && $record->status_cuti === 'Disetujui') {
            $original = $record->getOriginal('status_cuti');
            if ($original !== 'Disetujui') {
                $mulai = Carbon::parse($record->tanggal_mulai);
                $selesai = Carbon::parse($record->tanggal_selesai);
                $durasi = $mulai->diffInDays($selesai) + 1; // inklusif

                $karyawan = $record->karyawan;
                if ($karyawan instanceof Karyawan) {
                    $kuota = (int) ($karyawan->kuota_cuti_tahunan ?? 0);
                    $karyawan->kuota_cuti_tahunan = max(0, $kuota - $durasi);
                    $karyawan->save();
                }
            }
        }
    }
}

