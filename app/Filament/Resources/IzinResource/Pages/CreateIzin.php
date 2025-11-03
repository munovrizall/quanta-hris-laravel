<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Models\Izin;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIzin extends CreateRecord
{
    protected static string $resource = IzinResource::class;
    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Tambah Izin';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $lastIzin = Izin::withTrashed()
            ->where('izin_id', 'like', 'IZ%')
            ->orderBy('izin_id', 'desc')
            ->first();

        if ($lastIzin) {
            // Extract nomor dari ID terakhir (IZ0001 -> 1)
            $lastNumber = intval(str_replace('IZ', '', $lastIzin->izin_id));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $data['izin_id'] = 'IZ' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

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