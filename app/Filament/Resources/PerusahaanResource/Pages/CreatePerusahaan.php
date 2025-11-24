<?php

namespace App\Filament\Resources\PerusahaanResource\Pages;

use App\Filament\Resources\PerusahaanResource;
use App\Models\Perusahaan;
use Filament\Actions;
use App\Filament\Resources\Pages\CreateRecord;

class CreatePerusahaan extends CreateRecord
{
    protected static string $resource = PerusahaanResource::class;
    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Tambah Perusahaan';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $allIds = Perusahaan::withTrashed()
            ->pluck('perusahaan_id')
            ->map(function ($id) {
                return intval(substr($id, 1)); // Ambil angka dari P0001 -> 1
            })
            ->max();

        $nextNumber = ($allIds ?? 0) + 1;
        $data['perusahaan_id'] = 'P' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

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

