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

    public function mount(int|string $record = null): void
    {
        // Get route parameters
        $tahun = request()->route('tahun');
        $bulan = request()->route('bulan');

        // If using new URL format with tahun and bulan
        if ($tahun && $bulan) {
            $penggajian = Penggajian::forPeriode($bulan, $tahun)->first();

            if (!$penggajian) {
                abort(404, 'Penggajian tidak ditemukan untuk periode tersebut');
            }

            $this->record = $penggajian;
        } else {
            // Fallback to old method
            parent::mount($record);
        }
    }

    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        if (isset($this->record)) {
            return $this->record;
        }

        return parent::resolveRecord($key);
    }

    public function getBreadcrumbs(): array
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $breadcrumbs = parent::getBreadcrumbs();
        
        // Replace the last breadcrumb with period info
        if (isset($this->record)) {
            $periodeName = $namaBulan[$this->record->periode_bulan] . ' ' . $this->record->periode_tahun;
            $breadcrumbs[array_key_last($breadcrumbs)] = 'Edit ' . $periodeName;
        }
        
        return $breadcrumbs;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'tahun' => $this->record->periode_tahun,
            'bulan' => $this->record->periode_bulan
        ]);
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
