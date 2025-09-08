<?php

namespace App\Filament\Resources\LemburResource\Pages;

use App\Filament\Resources\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewLembur extends ViewRecord
{
  protected static string $resource = LemburResource::class;

  protected static ?string $title = 'Detail Lembur';

  protected static ?string $breadcrumb = 'Detail';

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->label('Ubah'),
      Actions\DeleteAction::make()
        ->label('Hapus')
        ->requiresConfirmation()
        ->modalHeading('Hapus Lembur')
        ->modalDescription('Apakah Anda yakin ingin menghapus data lembur ini?')
        ->modalSubmitActionLabel('Ya, hapus'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Lembur')
          ->schema([
            Infolists\Components\TextEntry::make('lembur_id')
              ->label('ID Lembur')
              ->badge()
              ->color('primary'),
            Infolists\Components\TextEntry::make('karyawan.nama_lengkap')
              ->label('Karyawan'),
            Infolists\Components\TextEntry::make('absensi.absensi_id')
              ->label('ID Absensi')
              ->badge()
              ->color('info'),
            Infolists\Components\TextEntry::make('tanggal_lembur')
              ->label('Tanggal Lembur')
              ->date()
              ->icon('heroicon-m-calendar'),
            Infolists\Components\TextEntry::make('durasi_lembur')
              ->label('Durasi Lembur')
              ->formatStateUsing(function ($state) {
                return $state ? date('H:i', strtotime($state)) . ' jam' : '-';
              })
              ->badge()
              ->color('info')
              ->icon('heroicon-m-clock'),
            Infolists\Components\TextEntry::make('status_lembur')
              ->label('Status Lembur')
              ->badge()
              ->color(fn(?string $state): string => match ($state) {
                'Diajukan' => 'warning',
                'Disetujui' => 'success',
                'Ditolak' => 'danger',
                default => 'gray',
              }),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Detail Pekerjaan')
          ->schema([
            Infolists\Components\TextEntry::make('deskripsi_pekerjaan')
              ->label('Deskripsi Pekerjaan')
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
            Infolists\Components\TextEntry::make('approver.nama_lengkap') // Tidak perlu diubah karena relasi sudah diupdate
              ->label('Diproses Oleh')
              ->placeholder('Belum ada persetujuan'),
            Infolists\Components\TextEntry::make('processed_at') // Changed from approved_at
              ->label('Waktu Diproses')
              ->dateTime()
              ->placeholder('Belum disetujui')
              ->icon('heroicon-m-calendar'),
            Infolists\Components\TextEntry::make('alasan_penolakan')
              ->label('Alasan Penolakan')
              ->placeholder('Tidak ada alasan penolakan')
              ->prose()
              ->visible(fn($record) => $record->status_lembur === 'Ditolak'),
          ])
          ->columns(2)
          ->visible(fn($record) => in_array($record->status_lembur, ['Disetujui', 'Ditolak'])),

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