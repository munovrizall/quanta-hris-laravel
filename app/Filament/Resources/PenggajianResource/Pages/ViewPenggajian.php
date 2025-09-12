<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPenggajian extends ViewRecord
{
  protected static string $resource = PenggajianResource::class;

  protected static ?string $title = 'Detail Penggajian';

  protected static ?string $breadcrumb = 'Detail';

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->label('Ubah'),
      Actions\DeleteAction::make()
        ->label('Hapus')
        ->requiresConfirmation()
        ->modalHeading('Hapus Penggajian')
        ->modalDescription('Apakah Anda yakin ingin menghapus penggajian ini?')
        ->modalSubmitActionLabel('Ya, hapus'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Penggajian')
          ->schema([
            Infolists\Components\TextEntry::make('penggajian_id')
              ->label('ID Penggajian')
              ->badge()
              ->color('primary'),

            Infolists\Components\TextEntry::make('periode')
              ->label('Periode')
              ->getStateUsing(function ($record): string {
                $namaBulan = [
                  1 => 'Januari',
                  2 => 'Februari',
                  3 => 'Maret',
                  4 => 'April',
                  5 => 'Mei',
                  6 => 'Juni',
                  7 => 'Juli',
                  8 => 'Agustus',
                  9 => 'September',
                  10 => 'Oktober',
                  11 => 'November',
                  12 => 'Desember'
                ];
                return $namaBulan[$record->periode_bulan] . ' ' . $record->periode_tahun;
              }),

            Infolists\Components\TextEntry::make('status_penggajian')
              ->label('Status')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'Draf' => 'gray',
                'Diverifikasi' => 'warning',
                'Disetujui' => 'success',
                'Ditolak' => 'danger',
                default => 'gray',
              }),

            Infolists\Components\TextEntry::make('total_karyawan')
              ->label('Total Karyawan')
              ->getStateUsing(fn($record): int => $record->slipGaji()->count())
              ->badge()
              ->color('info'),

            Infolists\Components\TextEntry::make('total_gaji')
              ->label('Total Gaji')
              ->getStateUsing(fn($record): string => 'Rp ' . number_format($record->slipGaji()->sum('total_gaji'), 0, ',', '.'))
              ->weight('bold')
              ->color('success'),

            Infolists\Components\TextEntry::make('created_at')
              ->label('Dibuat Pada')
              ->dateTime('d F Y H:i'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Alur Persetujuan')
          ->schema([
            Infolists\Components\TextEntry::make('verifier.nama_lengkap')
              ->label('Diverifikasi Oleh (Staff HRD)')
              ->default('Belum diverifikasi')
              ->icon('heroicon-m-user-check'),

            Infolists\Components\TextEntry::make('approver.nama_lengkap')
              ->label('Disetujui Oleh (Manager Finance)')
              ->default('Belum disetujui')
              ->icon('heroicon-m-check-badge'),

            Infolists\Components\TextEntry::make('processor.nama_lengkap')
              ->label('Diproses Oleh (Account Payment)')
              ->default('Belum diproses')
              ->icon('heroicon-m-cog-6-tooth'),
          ])
          ->columns(1),

        Infolists\Components\Section::make('Catatan')
          ->schema([
            Infolists\Components\TextEntry::make('catatan_penolakan_draf')
              ->label('Catatan Penolakan')
              ->default('Tidak ada catatan penolakan')
              ->columnSpanFull(),
          ])
          ->visible(fn($record) => $record->status_penggajian === 'Ditolak'),

        Infolists\Components\Section::make('Statistik')
          ->schema([
            Infolists\Components\TextEntry::make('slip_gaji_count')
              ->label('Slip Gaji Dibuat')
              ->getStateUsing(fn($record): int => $record->slipGaji()->count())
              ->badge()
              ->color('success'),

            Infolists\Components\TextEntry::make('slip_gaji_processed')
              ->label('Slip Gaji Diproses')
              ->getStateUsing(fn($record): int => $record->slipGaji()->where('status_slip', 'Diproses')->count())
              ->badge()
              ->color('warning'),
          ])
          ->columns(2),
      ]);
  }
}