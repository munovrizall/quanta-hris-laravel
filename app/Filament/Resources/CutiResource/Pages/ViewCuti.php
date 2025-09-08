<?php

namespace App\Filament\Resources\CutiResource\Pages;

use App\Filament\Resources\CutiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCuti extends ViewRecord
{
  protected static string $resource = CutiResource::class;

  protected static ?string $title = 'Detail Cuti';

  protected static ?string $breadcrumb = 'Detail';

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->label('Ubah'),
      Actions\DeleteAction::make()
        ->label('Hapus')
        ->requiresConfirmation()
        ->modalHeading('Hapus Cuti')
        ->modalDescription('Apakah Anda yakin ingin menghapus data cuti ini?')
        ->modalSubmitActionLabel('Ya, hapus'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Cuti')
          ->schema([
            Infolists\Components\TextEntry::make('cuti_id')
              ->label('ID Cuti')
              ->badge()
              ->color('primary'),
            Infolists\Components\TextEntry::make('karyawan.nama_lengkap')
              ->label('Karyawan'),
            Infolists\Components\TextEntry::make('jenis_cuti')
              ->label('Jenis Cuti')
              ->badge()
              ->color('info'),
            Infolists\Components\TextEntry::make('tanggal_mulai')
              ->label('Tanggal Mulai')
              ->date()
              ->icon('heroicon-m-calendar'),
            Infolists\Components\TextEntry::make('tanggal_selesai')
              ->label('Tanggal Selesai')
              ->date()
              ->icon('heroicon-m-calendar'),
            Infolists\Components\TextEntry::make('durasi_cuti')
              ->label('Durasi Cuti')
              ->formatStateUsing(fn($record) => $record->durasi_cuti . ' hari')
              ->badge()
              ->color('secondary')
              ->icon('heroicon-m-clock'),
            Infolists\Components\TextEntry::make('status_cuti')
              ->label('Status Cuti')
              ->badge()
              ->color(fn(?string $state): string => match ($state) {
                'Diajukan' => 'warning',
                'Disetujui' => 'success',
                'Ditolak' => 'danger',
                default => 'gray',
              }),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Detail Cuti')
          ->schema([
            Infolists\Components\TextEntry::make('keterangan')
              ->label('Keterangan')
              ->prose(),
            Infolists\Components\TextEntry::make('dokumen_pendukung')
              ->label('Dokumen Pendukung')
              ->formatStateUsing(fn($state) => $state ? basename($state) : 'Tidak ada dokumen')
              ->url(fn($state) => $state ? asset('storage/' . $state) : null)
              ->openUrlInNewTab()
              ->icon('heroicon-m-document'),
          ])
          ->columns(1),

        Infolists\Components\Section::make('Informasi Persetujuan')
          ->schema([
            Infolists\Components\TextEntry::make('approver.nama_lengkap')
              ->label('Disetujui Oleh')
              ->placeholder('Belum ada persetujuan'),
            Infolists\Components\TextEntry::make('processed_at')
              ->label('Waktu Persetujuan')
              ->dateTime()
              ->placeholder('Belum disetujui')
              ->icon('heroicon-m-calendar'),
            Infolists\Components\TextEntry::make('alasan_penolakan')
              ->label('Alasan Penolakan')
              ->placeholder('Tidak ada alasan penolakan')
              ->prose()
              ->visible(fn($record) => $record->status_cuti === 'Ditolak'),
          ])
          ->columns(2)
          ->visible(fn($record) => in_array($record->status_cuti, ['Disetujui', 'Ditolak'])),

        Infolists\Components\Section::make('Informasi Sistem')
          ->schema([
            Infolists\Components\TextEntry::make('created_at')
              ->label('Dibuat')
              ->dateTime(),
            Infolists\Components\TextEntry::make('updated_at')
              ->label('Diubah')
              ->dateTime(),
          ])
          ->columns(2)
          ->collapsed(),
      ]);
  }
}