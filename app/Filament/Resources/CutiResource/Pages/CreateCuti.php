<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Filament\Resources\CutiResource;
use App\Models\Cuti;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCuti extends CreateRecord
{
    protected static string $resource = CutiResource::class;

    protected static ?string $title = 'Tambah Cuti';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $lastCuti = Cuti::withTrashed()
            ->where('cuti_id', 'like', 'CT%')
            ->orderBy('cuti_id', 'desc')
            ->first();

        if ($lastCuti) {
            // Extract nomor dari ID terakhir (CT0001 -> 1)
            $lastNumber = intval(str_replace('CT', '', $lastCuti->cuti_id));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $data['cuti_id'] = 'CT' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

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