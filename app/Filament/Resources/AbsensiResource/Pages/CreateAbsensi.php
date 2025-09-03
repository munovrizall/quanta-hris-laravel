<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;

    protected static ?string $title = 'Tambah Absensi';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $allIds = Absensi::withTrashed()
            ->pluck('absensi_id')
            ->map(function ($id) {
                // Ambil 4 digit terakhir dari AB0001 -> 0001 -> 1
                return intval(substr($id, 2)); // Menggunakan substr($id, 2) untuk skip "AB"
            })
            ->max();

        $nextNumber = ($allIds ?? 0) + 1;
        $data['absensi_id'] = 'AB' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Tambah Lagi');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }
}