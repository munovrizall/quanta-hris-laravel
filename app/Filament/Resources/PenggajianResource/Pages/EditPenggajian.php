<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Penggajian;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

class EditPenggajian extends EditRecord
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Ubah Penggajian';

    protected static ?string $breadcrumb = 'Ubah';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat')
                ->url(fn () => static::getResource()::getUrl('view', [
                    'tahun' => $this->record->periode_tahun,
                    'bulan' => $this->record->periode_bulan
                ])),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Penggajian')
                ->modalDescription('Apakah Anda yakin ingin menghapus seluruh data penggajian untuk periode ini?')
                ->modalSubmitActionLabel('Ya, hapus')
                ->action(function (Penggajian $record) {
                    Penggajian::where('periode_bulan', $record->periode_bulan)
                             ->where('periode_tahun', $record->periode_tahun)
                             ->delete();
                })
                ->successRedirectUrl(static::getResource()::getUrl('index')),
            Actions\ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->action(function (Penggajian $record) {
                    Penggajian::where('periode_bulan', $record->periode_bulan)
                             ->where('periode_tahun', $record->periode_tahun)
                             ->withTrashed()
                             ->forceDelete();
                })
                ->successRedirectUrl(static::getResource()::getUrl('index')),
            Actions\RestoreAction::make()
                ->label('Pulihkan')
                ->action(function (Penggajian $record) {
                    Penggajian::where('periode_bulan', $record->periode_bulan)
                             ->where('periode_tahun', $record->periode_tahun)
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
            $penggajian = Penggajian::where('periode_bulan', $bulan)
                                   ->where('periode_tahun', $tahun)
                                   ->first();

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
            ->label('Simpan');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->url($this->getResource()::getUrl('view', [
                'tahun' => $this->record->periode_tahun,
                'bulan' => $this->record->periode_bulan
            ]));
    }
}

