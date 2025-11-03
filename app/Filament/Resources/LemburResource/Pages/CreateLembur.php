<?php

namespace App\Filament\Resources\LemburResource\Pages;

use App\Filament\Resources\LemburResource;
use App\Models\Lembur;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLembur extends CreateRecord
{
    protected static string $resource = LemburResource::class;
    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Tambah Lembur';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cari ID tertinggi yang pernah digunakan (termasuk soft deleted)
        $lastLembur = Lembur::withTrashed()
            ->where('lembur_id', 'like', 'LB%')
            ->orderBy('lembur_id', 'desc')
            ->first();

        if ($lastLembur) {
            // Extract nomor dari ID terakhir (LB0001 -> 1)
            $lastNumber = intval(str_replace('LB', '', $lastLembur->lembur_id));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $data['lembur_id'] = 'LB' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

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