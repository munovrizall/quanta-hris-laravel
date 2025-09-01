<?php

namespace App\Filament\Resources\CabangResource\Pages;

use App\Filament\Resources\CabangResource;
use App\Models\Cabang;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCabang extends CreateRecord
{
    protected static string $resource = CabangResource::class;

    protected static ?string $title = 'Tambah Cabang';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $allIds = Cabang::withTrashed()
            ->pluck('cabang_id')
            ->map(function ($id) {
                return intval(substr($id, 1)); // Ambil angka dari C0001 -> 1
            })
            ->max();

        $nextNumber = ($allIds ?? 0) + 1;
        $data['cabang_id'] = 'C' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

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