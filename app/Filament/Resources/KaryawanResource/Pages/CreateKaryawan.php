<?php

namespace App\Filament\Resources\KaryawanResource\Pages;

use App\Filament\Resources\KaryawanResource;
use App\Models\Karyawan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKaryawan extends CreateRecord
{
    protected static string $resource = KaryawanResource::class;

    protected static ?string $title = 'Tambah Karyawan';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $allIds = Karyawan::withTrashed()
            ->pluck('karyawan_id')
            ->map(function ($id) {
                return intval(substr($id, 1)); // Ambil angka dari K0001 -> 1
            })
            ->max();

        $nextNumber = ($allIds ?? 0) + 1;
        $data['karyawan_id'] = 'K' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

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