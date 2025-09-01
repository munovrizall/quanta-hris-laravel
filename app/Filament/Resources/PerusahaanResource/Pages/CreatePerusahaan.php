<?php

namespace App\Filament\Resources\PerusahaanResource\Pages;

use App\Filament\Resources\PerusahaanResource;
use App\Models\Perusahaan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePerusahaan extends CreateRecord
{
    protected static string $resource = PerusahaanResource::class;

    protected static ?string $title = 'Tambah Perusahaan';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto generate perusahaan_id
        $lastPerusahaan = Perusahaan::orderBy('perusahaan_id', 'desc')->first();

        if ($lastPerusahaan) {
            // Ambil nomor dari ID terakhir (misal P0001 -> 1)
            $lastNumber = intval(substr($lastPerusahaan->perusahaan_id, 1));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format menjadi P0001, P0002, dst
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